<?php
/**
 * quiz hidden question info set template
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Allcreator <info@allcreator.net>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>
<?php
	echo $this->NetCommonsForm->hidden('QuizPage.{{pageIndex}}.QuizQuestion.{{qIndex}}.question_sequence',
		array('ng-value' => 'question.questionSequence'));
	echo $this->NetCommonsForm->hidden('QuizPage.{{pageIndex}}.QuizQuestion.{{qIndex}}.key',
		array('ng-value' => 'question.key'));
