<?php
/**
 * QuizResultHelper::getPassing()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author AllCreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsHelperTestCase', 'NetCommons.TestSuite');
App::uses('QuizzesComponent', 'Quizzes.Controller/Component');
App::uses('WorkflowComponent', 'Workflow.Controller/Component');

/**
 * QuizResultHelper::getPassing()のテスト
 *
 * @author AllCreator <info@allcreator.net>
 * @package NetCommons\Quizzes\Test\Case\View\Helper\QuizResultHelper
 */
class QuizResultHelperGetPassingTest extends NetCommonsHelperTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array();

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'quizzes';

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		//テストデータ生成
		//必要に応じてセットする
		$viewVars = array();
		$requestData = array();
		$params = array();

		//Helperロード
		$this->loadHelper('Quizzes.QuizResult', $viewVars, $requestData, $params);
	}

/**
 * getPassing()のテスト
 *
 * @return void
 */
	public function testGetPassing1() {
		//データ生成
		$quiz = array(
			'Quiz' => array(
				'key' => 'test_quiz',
				'status' => WorkflowComponent::STATUS_PUBLISHED,
				'passing_grade' => 0,
			)
		);
		$summary = array();
		$result = $this->QuizResult->getPassing($quiz, $summary);
		$this->assertEmpty($result);
	}
/**
 * getPassing()のテスト
 *
 * @return void
 */
	public function testGetPassing2() {
		//データ生成
		$quiz = array(
			'Quiz' => array(
				'key' => 'test_quiz',
				'status' => WorkflowComponent::STATUS_PUBLISHED,
				'passing_grade' => 10,
			)
		);
		$summary = array(
			'Statistics' => array('passing_status' => QuizzesComponent::STATUS_GRADE_FAIL)
		);
		$result = $this->QuizResult->getPassing($quiz, $summary);
		$this->assertEmpty($result);
	}

}