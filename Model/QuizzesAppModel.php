<?php
/**
 * Quizzes App Model
 *
 * @property Block $Block
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author AllCreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
App::uses('AppModel', 'Model');
/**
 * Summary for Quizzes Model
 */
class QuizzesAppModel extends AppModel {

/**
 * getPeriodStatus
 * get period status now and specified time
 *
 * @param bool $check flag data
 * @param string $startTime start time
 * @param string $endTime end time
 * @return int
 */
	public function getPeriodStatus($check, $startTime, $endTime) {
		$ret = QuizzesComponent::QUIZ_PERIOD_STAT_IN;
		if ($check == QuizzesComponent::USES_USE) {
			$nowTime = strtotime((new NetCommonsTime())->getNowDatetime());
			if (! empty($startTime) && $nowTime < strtotime($startTime)) {
				$ret = QuizzesComponent::QUIZ_PERIOD_STAT_BEFORE;
			}
			if (! empty($endTime) && $nowTime > strtotime($endTime)) {
				$ret = QuizzesComponent::QUIZ_PERIOD_STAT_END;
			}
		}
		return $ret;
	}
/**
 * hasPublished method
 *
 * @param array $quiz quiz data
 * @return int
 */
	public function hasPublished($quiz) {
		if (isset($quiz['Quiz']['key'])) {
			$isPublished = $this->find('count', array(
				'recursive' => -1,
				'conditions' => array(
					'is_active' => true,
					'key' => $quiz['Quiz']['key']
				)
			));
		} else {
			$isPublished = 0;
		}
		return $isPublished;
	}

/**
 * clearQuizId 小テストデータからＩＤのみをクリアする
 *
 * @param array &$quiz 小テストデータ
 * @param bool $isIdOnly 純粋にIDフィールドのみをクリアするのか
 * @return void
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
	public function clearQuizId(&$quiz, $isIdOnly = false) {
		foreach ($quiz as $qKey => $q) {
			if (is_array($q)) {
				$this->clearQuizId($quiz[$qKey], $isIdOnly);
			} else {
				if ($isIdOnly) {
					$judge = preg_match('/^id$/', $qKey);
				} else {
					$judge = preg_match('/^id$/', $qKey) ||
					preg_match('/^key$/', $qKey) ||
					preg_match('/^created(.*?)/', $qKey) ||
					preg_match('/^modified(.*?)/', $qKey);
				}
				if ($judge) {
					unset($quiz[$qKey]);
				}
			}
		}
	}

}
