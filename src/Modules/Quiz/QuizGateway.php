<?php

namespace Foodsharing\Modules\Quiz;

use Foodsharing\Modules\Bell\BellGateway;
use Foodsharing\Modules\Core\BaseGateway;
use Foodsharing\Modules\Core\Database;
use Foodsharing\Modules\Core\DBConstants\Quiz\QuizStatus;
use Foodsharing\Modules\Core\DBConstants\Quiz\SessionStatus;
use Foodsharing\Modules\Foodsaver\FoodsaverGateway;

class QuizGateway extends BaseGateway
{
	private $bellGateway;
	private $foodsaverGateway;
	private $quizSessionGateway;

	public function __construct(
		Database $db,
		BellGateway $bellGateway,
		FoodsaverGateway $foodsaverGateway,
		QuizSessionGateway $quizSessionGateway
	) {
		parent::__construct($db);

		$this->bellGateway = $bellGateway;
		$this->foodsaverGateway = $foodsaverGateway;
		$this->quizSessionGateway = $quizSessionGateway;
	}

	public function getQuizzes(): array
	{
		return $this->db->fetchAll('
			SELECT id, name
			FROM fs_quiz
			ORDER BY id
		');
	}

	public function addQuiz(string $name, string $desc, int $maxFailurePoints, int $questionCount): int
	{
		return $this->db->insert('fs_quiz',
			[
				'name' => $name,
				'desc' => $desc,
				'maxfp' => $maxFailurePoints,
				'questcount' => $questionCount
			]
		);
	}

	public function updateQuiz(int $id, string $name, string $desc, string $maxFailurePoints, string $questionCount): int
	{
		return $this->db->update(
			'fs_quiz',
			[
				'name' => $name,
				'desc' => $desc,
				'maxfp' => $maxFailurePoints,
				'questcount' => $questionCount
			],
			['id' => $id]
		);
	}

	public function getQuiz(int $id): array
	{
		return $this->db->fetchByCriteria(
			'fs_quiz',
			['id', 'name', 'desc', 'maxfp', 'questcount'],
			['id' => $id]
		);
	}

	public function getQuizName(int $quizId): string
	{
		$quiz = $this->getQuiz($quizId);

		return $quiz ? $quiz['name'] : '';
	}

	public function getQuizStatus(int $quizId, int $fsId): QuizStatus
	{
		$quizSessionStatus = $this->quizSessionGateway->collectQuizStatus($quizId, $fsId);

		if ($quizSessionStatus['times'] == 0) {
			return QuizStatus::NEVER_TRIED;
		} elseif ($quizSessionStatus['running'] > 0) {
			return QuizStatus::RUNNING;
		} elseif ($quizSessionStatus['passed'] > 0) {
			return QuizStatus::PASSED;
		} elseif ($quizSessionStatus['failed'] < 3) {
			return QuizStatus::FAILED;
		} elseif ($quizSessionStatus['failed'] == 3 && (time() - $quizSessionStatus['last_try']) < (86400 * 30)) {
			return QuizStatus::PAUSE;
		} elseif ($quizSessionStatus['failed'] >= 3 && $quizSessionStatus['failed'] < 5 && (time() - $quizSessionStatus['last_try']) >= (86400 * 14)) {
			return QuizStatus::PAUSE_ELAPSED;
		}

		return QuizStatus::DISQUALIFIED;
	}

	public function hasPassedQuiz(int $fsId, int $quizId): bool
	{
		$passedCount = $this->quizSessionGateway->countSessions($fsId, $quizId, SessionStatus::PASSED);

		return $passedCount > 0;
	}

	public function setFsQuizRole(int $fsId, int $quizRole): int
	{
		return $this->db->update(
			'fs_foodsaver',
			['quiz_rolle' => $quizRole],
			['id' => $fsId]
		);
	}

	public function addQuestion(int $quizId, string $text, int $failurePoints, int $duration): int
	{
		$questionId = $this->db->insert(
			'fs_question',
			[
				'text' => $text,
				'duration' => $duration
			]
		);
		if ($questionId > 0) {
			$this->db->insert(
				'fs_question_has_quiz',
				[
					'question_id' => $questionId,
					'quiz_id' => $quizId,
					'fp' => $failurePoints
				]
			);

			return $questionId;
		}

		return 0;
	}

	public function getQuestion(int $questionId): array
	{
		return $this->db->fetch('
			SELECT
					q.id,
					q.`text`,
					q.duration,
					q.wikilink,
					hq.fp,
					hq.quiz_id

				FROM
					fs_question q
					LEFT JOIN fs_question_has_quiz hq
					ON hq.question_id = q.id

				WHERE
					q.id = :questionId
		', ['questionId' => $questionId]);
	}

	public function getRandomQuestions(int $count, int $failurePoints, int $quizId): array
	{
		return $this->db->fetchAll('
			SELECT
				q.id,
				q.duration,
				hq.fp

			FROM
				fs_question q
				LEFT JOIN fs_question_has_quiz hq
				ON hq.question_id = q.id

			WHERE
				hq.quiz_id = :quizId
			AND
				hq.fp = :fp

			ORDER BY
				RAND()

			LIMIT :count
		', ['quizId' => $quizId, 'fp' => $failurePoints, 'count' => $count]);
	}

	public function getQuestionMetas(int $quizId): array
	{
		$questions = $this->db->fetchAll('
			SELECT
				q.id,
				q.duration,
				hq.fp

			FROM
				fs_question q
				LEFT JOIN fs_question_has_quiz hq
				ON hq.question_id = q.id

			WHERE
				hq.quiz_id = :quizId
		', ['quizId' => $quizId]);
		if ($questions) {
			$outmeta = array();
			$meta = $this->db->fetchAll('
				SELECT 	hq.fp, COUNT(q.id) AS `count`
				FROM fs_question q
					LEFT JOIN fs_question_has_quiz hq
					ON hq.question_id = q.id

				WHERE
					hq.quiz_id = :quizId

				GROUP BY
					hq.fp
			', ['quizId' => $quizId]);
			if ($meta) {
				foreach ($meta as $m) {
					if (!isset($outmeta[$m['fp']])) {
						$outmeta[$m['fp']] = $m['count'];
					}
				}
			}

			return array(
				'meta' => $outmeta,
				'question' => $questions
			);
		}

		return [];
	}

	public function updateQuestion(int $questionId, int $quizId, string $text, int $failurePoints, int $duration, string $wikiLink): void
	{
		$this->db->update(
			'fs_question',
			[
				'text' => $text,
				'duration' => $duration,
				'wikilink' => $wikiLink
			],
			['id' => $questionId]
		);

		$this->db->update(
			'fs_question_has_quiz',
			['fp' => $failurePoints],
			[
				'question_id' => $questionId,
				'quiz_id' => $quizId
			]
		);
	}

	public function deleteQuestion(int $questionId): void
	{
		$this->db->delete('fs_answer', ['question_id' => $questionId]);
		$this->db->delete('fs_question', ['id' => $questionId]);
		$this->db->delete('fs_question_has_quiz', ['question_id' => $questionId]);
	}

	public function listQuestions(int $quizId): array
	{
		$questions = $this->getQuestions($quizId);
		if ($questions) {
			foreach ($questions as $key => $q) {
				$questions[$key]['answers'] = $this->getAnswers($q['id']);
				$questions[$key]['comment_count'] = $this->getCommentCount($q['id']);
			}

			return $questions;
		}

		return [];
	}

	private function getCommentCount(int $questionId): int
	{
		return $this->db->fetchValue('
			SELECT COUNT(question_id)
			FROM fs_question_has_wallpost
			WHERE question_id = :questionId
		', ['questionId' => $questionId]);
	}

	public function getRightQuestions(int $quizId): array
	{
		$out = array();
		$questions = $this->getQuestions($quizId);
		if ($questions) {
			foreach ($questions as $q) {
				$questionId = $q['id'];
				$out[$questionId] = $q;
				$answers = $this->getAnswers($questionId);
				if ($answers) {
					$out[$questionId]['answers'] = array();
					foreach ($answers as $a) {
						$out[$questionId]['answers'][$a['id']] = $a;
					}
				}
			}

			return $out;
		}

		return [];
	}

	private function getQuestions(int $quizId): array
	{
		return $this->db->fetchAll('
			SELECT
				q.id,
				q.text,
				q.duration,
				q.wikilink,
				hq.fp

			FROM
				fs_question q
				LEFT JOIN fs_question_has_quiz hq
				ON hq.question_id = q.id

			WHERE
				hq.quiz_id = :quizId
		', ['quizId' => $quizId]);
	}

	public function addAnswer(int $questionId, string $text, string $explanation, int $right): int
	{
		return $this->db->insert(
			'fs_answer',
			[
				'question_id' => $questionId,
				'text' => $text,
				'explanation' => $explanation,
				'right' => $right
			]
		);
	}

	public function getAnswer(int $answerId): array
	{
		return $this->db->fetchByCriteria(
			'fs_answer',
			['id', 'question_id', 'text', 'explanation', 'right'],
			['id' => $answerId]
		);
	}

	public function getAnswers(int $questionId): array
	{
		return $this->db->fetchAllByCriteria(
			'fs_answer',
			['id', 'text', 'explanation', 'right'],
			['question_id' => $questionId]
		);
	}

	public function updateAnswer(int $answerId, string $text, string $explanation, int $right): int
	{
		return $this->db->update(
			'fs_answer',
			[
				'text' => $text,
				'explanation' => $explanation,
				'right' => $right
			],
			['id' => $answerId]
		);
	}

	public function deleteAnswer(int $answerId): int
	{
		return $this->db->delete('fs_answer', ['id' => $answerId]);
	}

	public function addUserComment(int $questionId, int $fsId, string $comment): bool
	{
		$commentId = $this->db->insert(
			'fs_wallpost',
			[
				'foodsaver_id' => $fsId,
				'body' => $comment,
				'time' => $this->db->now()
			]
		);

		return $this->handleUserComment($questionId, $commentId, $comment);
	}

	private function handleUserComment(int $questionId, int $commentId, string $comment): bool
	{
		if ($commentId > 0) {
			if ($quizAMBs = $this->foodsaverGateway->getBotschafter(341)) {
				$this->bellGateway->addBell(
					$quizAMBs,
					'new_quiz_comment_title',
					'new_quiz_comment',
					'fas fa-question-circle',
					array('href' => '/?page=quiz&sub=wall&id=' . $questionId),
					array('comment' => $comment)
				);
			}
			$this->db->insert(
				'fs_question_has_wallpost',
				[
					'question_id' => $questionId,
					'wallpost_id' => $commentId,
					'usercomment' => 1
				]
			);

			return true;
		}

		return false;
	}
}
