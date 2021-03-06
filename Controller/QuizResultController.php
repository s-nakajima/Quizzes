<?php
/**
 * QuizResult Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Allcreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppController', 'Controller');

/**
 * QuizResultController
 *
 * @author Allcreator <info@allcreator.net>
 * @package NetCommons\Quizzes\Controller
 */
class QuizResultController extends QuizzesAppController {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Quizzes.QuizAnswerSummary',
		'Quizzes.QuizAnswer',
		'Quizzes.QuizResult',
		'Quizzes.QuizFrameSetting'
	);

/**
 * use components
 *
 * @var array
 */
	public $components = array(
		'NetCommons.Permission' => array(
			//アクセスの権限
			'allow' => array(
				'index' => 'block_editable',
			),
		),
		'Quizzes.QuizzesOwnAnswerQuiz',	// 回答済み小テスト管理
		'Quizzes.QuizzesOwnAnswer',		// 回答ID管理
		'Quizzes.QuizzesPassQuiz',		// 合格小テスト管理
		'Paginator' => array('className' => 'NetCommons.AppPaginator'),
	);

/**
 * use helpers
 *
 */
	public $helpers = [
		'NetCommons.Date',
		'NetCommons.DisplayNumber',
		'NetCommons.TitleIcon',
		'NetCommons.TableList',
		'Workflow.Workflow',
		'Quizzes.QuizResult',
		'Quizzes.QuizGradeLink',
	];

/**
 * target quiz data
 *
 */
	private $__quiz = null;

/**
 * beforeFilter
 * NetCommonsお約束：できることならControllerのbeforeFilterで実行可/不可の判定して流れを変える
 *
 * @return void
 */
	public function beforeFilter() {
		// 親クラスのbeforeFilterを済ませる
		parent::beforeFilter();

		// NetCommonsお約束：編集画面へのURLに編集対象のコンテンツキーが含まれている
		// まずは、そのキーを取り出す
		$quizKey = $this->_getQuizKeyFromPass();

		// キーで指定されたデータを取り出しておく
		$conditions = $this->Quiz->getResultViewCondition(
			array('Quiz.key' => $quizKey)
		);
		$this->__quiz = $this->Quiz->find('first', array(
			'conditions' => $conditions,
		));
		if (! $this->__quiz) {
			$this->setAction('throwBadRequest');
		}
		// 表示方法設定値取得
		list($displayType) =
			$this->QuizFrameSetting->getQuizFrameSetting(Current::read('Frame.key'));
		$this->set('displayType', $displayType);
	}

/**
 * index
 * 編集権限を持つ人物だけがこの画面を見ることができる
 *
 * @return void
 */
	public function index() {
		$quiz = $this->__quiz;

		// 集計処理モデル初期設定処理
		$this->QuizResult->initResult($quiz);

		// 総合情報取得
		// 得点分布データ取得
		$general = $this->QuizResult->getAllResult();

		$options = $this->QuizResult->getPaginateOptions();
		$this->Paginator->settings = array_merge(
			$this->Paginator->settings,
			array(
				'page' => 1,
				'limit' => 10,
				'order' => array('User.handlename' => 'DESC'),
			),
			$options
		);
		$filter = $this->_getFilter();
		$summaryList = $this->paginate(
			'QuizResult',
			$filter,
			array(
				'User.handlename',
				'QuizAnswerSummary.id',
				'QuizAnswerSummary.answer_number',
				'QuizAnswerSummary.summary_score',
				'Statistics.avg_elapsed_second',
				'Statistics.max_score',
				'Statistics.min_score',
			)
		);

		$this->set('quiz', $quiz);
		$this->set('general', $general);
		$this->set('summaryList', $summaryList);
		$this->set('passFilterStatus', $this->_getParam('passing_status'));
		$this->set('winthinTimeFilterStatus', $this->_getParam('within_time_status'));
	}

/**
 * view method
 * Display the question of the quiz , to accept the answer input
 *
 * @return void
 */
	public function view() {
		$quiz = $this->__quiz;

		// 基本的には自分の履歴を見ようとしていることが前提
		// 初期表示のテスト一覧から結果を見ようとしている＝つまり編集権限は無し、自分のデータを見るパターン
		$userId = Current::read('User.id');
		$handleName = Current::read('User.handlename');

		// サマリIDが指定されているときだけが、上記の前提から異なる可能性があるパターン
		// 成績一覧画面(quiz_result/index）からこの画面へ来たときの遷移
		// この時は対象人物が自分ではないので適宜設定しなおす
		// サマリID
		$summaryId = null;
		if (isset($this->params['pass'][0])) {
			$summaryId = $this->params['pass'][0];
			$summary = $this->QuizAnswerSummary->findById($summaryId);
			if (! $summary) {
				$this->setAction('throwBadRequest');
			}
			$userId = $summary['QuizAnswerSummary']['user_id'];
			$handleName = $summary['User']['handlename'];
		}

		if (! $handleName) {
			$handleName = __d('quizzes', 'Guest');
		}

		// 採点する権限を持っていないなら 自分自身のデータであることが必要
		$canGrade = $this->canGrade($quiz);
		if (! $canGrade && $summaryId) {
			// 自分の？
			if (! $this->QuizzesOwnAnswer->checkOwnAnsweredSummaryId($summaryId)) {
				$this->setAction('throwBadRequest');
			}
		}
		// 初期設定
		$this->QuizResult->initResult($quiz);
		// 統合情報取得
		$general = $this->QuizResult->getAllResult();

		// 得点推移データ取得
		// そのサマリIDに該当する人物のサマリ履歴を取得する
		$scoreHistory = $this->_getScoreHistory($quiz, $userId, $summaryId);

		$conditions = array(
			'quiz_key' => $quiz['Quiz']['key'],
		);
		if ($userId) {
			$conditions['user_id'] = $userId;
		} else {
			// 非会員
			// サマリID指定
			if ($summaryId) {
				$conditions['id'] = $summaryId;
			} else {
				$conditions['id'] = $this->QuizzesOwnAnswer->getAnsweredSummaryIds();
			}
		}
		$this->paginate = array(
			'conditions' => $conditions,
			'page' => 1,
			'order' => array('QuizAnswerSummary.answer_number' => 'DESC'),
			'limit' => 10,
			'recursive' => -1,
		);
		$summaryList = $this->paginate('QuizAnswerSummary');

		$this->set('quiz', $quiz);
		$this->set('userId', $userId);
		$this->set('handleName', $handleName);
		$this->set('general', $general);
		$this->set('summaryList', $summaryList);
		$this->set('scoreHistory', $scoreHistory);
	}

/**
 * _getScoreHistory
 *
 * 得点履歴取得
 *
 * @param array $quiz 小テストデータ
 * @param int $userId ユーザーID
 * @param int $summaryId サマリID
 * @return array
 */
	protected function _getScoreHistory($quiz, $userId, $summaryId) {
		if (! $userId) {
			// 非会員データの場合は履歴を持たない（１回限り
			return false;
		}
		$conditions = array(
			'quiz_key' => $quiz['Quiz']['key'],
			//'is_grade_finished' => true,
			'user_id' => $userId,
		);
		$scoreHistory = $this->QuizAnswerSummary->find('all', array(
			'fields' => array('answer_number', 'summary_score', 'is_grade_finished'),
			'conditions' => $conditions,
			'recursive' => -1,
			'order' => array('answer_number' => 'ASC')
		));
		if ($scoreHistory) {
			$scoreHistory = Hash::extract($scoreHistory, '{n}.QuizAnswerSummary');
		}
		return $scoreHistory;
	}

/**
 * _getParam method
 * パラメータ取り出し
 *
 * @param string $name パラメータ名
 * @return string
 */
	protected function _getParam($name) {
		if (isset($this->request->named[$name])) {
			return $this->request->named[$name];
		}
		return '';
	}
/**
 * _getFilter method
 * 絞込条件取り出し
 *
 * @return array
 */
	protected function _getFilter() {
		$filter = array();
		$filter['QuizAnswerSummary.passing_status'] = $this->_getParam('passing_status');
		$filter['QuizAnswerSummary.within_time_status'] = $this->_getParam('within_time_status');
		$filter = Hash::filter($filter);
		return $filter;
	}
}
