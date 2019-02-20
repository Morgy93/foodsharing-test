<?php

namespace Foodsharing\Modules\Quiz;

use Foodsharing\Modules\Core\Control;

class QuizControl extends Control
{
	public function __construct(QuizModel $model, QuizView $view)
	{
		$this->model = $model;
		$this->view = $view;

		parent::__construct();

		if (!$this->session->may()) {
			$this->func->goLogin();
		} elseif (!$this->session->mayEditQuiz()) {
			$this->func->go('/');
		}
	}

	public function index()
	{
		// quiz&a=delete&id=9
		if ($id = $this->func->getActionId('delete')) {
			$this->model->deleteSession($id);
			$this->goBack();
		}

		$this->func->addBread('Quiz', '/?page=quiz');
		$this->func->addTitle('Quiz');

		$topbtn = '';
		$slogan = 'Quiz-Fragen für Foodsaver, Betriebsverantwortliche & Botschafter';
		if (!isset($_GET['sub']) && isset($_GET['id']) && (int)$_GET['id'] > 0) {
			if ($name = $this->model->getVal('name', 'quiz', $_GET['id'])) {
				$this->func->addBread($name, '/?page=quiz&id=' . (int)$_GET['id']);
				$topbtn = ' - ' . $name;
				$slogan = 'Klausurfragen für ' . $name;
			}
			$this->listQuestions($_GET['id']);
		}

		if (!isset($_GET['sub'])) {
			if (!isset($_GET['id'])) {
				$this->func->go('/?page=quiz&id=1');
			}
			$this->func->addContent($this->view->topbar('Quiz' . $topbtn, $slogan, '<img src="/img/quiz.png" />'), CNT_TOP);
			$this->func->addContent($this->view->listQuiz($this->model->listQuiz()), CNT_LEFT);
			$this->func->addContent($this->view->quizMenu(), CNT_LEFT);
		}
	}

	private function goBack()
	{
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		exit();
	}

	public function wall()
	{
		if ($q = $this->model->getQuestion($_GET['id'])) {
			if ($name = $this->model->getVal('name', 'quiz', $q['quiz_id'])) {
				$this->func->addBread($name, '/?page=quiz&id=' . (int)$_GET['id']);
			}
			$this->func->addBread('Frage  #' . $q['id'], '/?page=quiz&sub=wall&id=' . (int)$q['id']);
			$this->func->addContent($this->view->topbar('Quizfrage  #' . $q['id'], '<a style="float:right;color:#FFF;font-size:13px;margin-top:-20px;" href="#" class="button" onclick="ajreq(\'editquest\',{id:' . (int)$q['id'] . ',qid:' . (int)$q['quiz_id'] . '});return false;">Frage bearbeiten</a>' . $q['text'] . '<p><strong>' . $q['fp'] . ' Fehlerpunkte, ' . $q['duration'] . ' Sekunden zum Antworten</strong></p>', '<img src="/img/quiz.png" />'), CNT_TOP);
			$this->func->addContent($this->v_utils->v_field($this->wallposts('question', $_GET['id']), 'Kommentare'), CNT_MAIN);
			$this->func->addContent($this->view->answerSidebar($this->model->getAnswers($q['id']), $_GET['id']), CNT_RIGHT);
		}
	}

	public function edit()
	{
		if ($quiz = $this->model->getQuiz($_GET['qid'])) {
			if ($this->isSubmitted()) {
				$name = strip_tags($_POST['name']);
				$name = trim($name);

				$desc = $_POST['desc'];
				$desc = trim($desc);

				$maxfp = (int)$_POST['maxfp'];
				$questcount = (int)$_POST['questcount'];

				if (!empty($name)) {
					if ($id = $this->model->updateQuiz($_GET['qid'], $name, $desc, $maxfp, $questcount)) {
						$this->func->info('Quiz wurde erfolgreich geändert!');
						$this->func->go('/?page=quiz&id=' . (int)$id);
					}
				}
			}
			$this->func->setEditData($quiz);
			$this->func->addContent($this->view->quizForm());
		}
	}

	public function newquiz()
	{
		if ($this->isSubmitted()) {
			$name = strip_tags($_POST['name']);
			$name = trim($name);

			$desc = $_POST['desc'];
			$desc = trim($desc);

			$maxfp = (int)$_POST['maxfp'];
			$questcount = (int)$_POST['questcount'];

			if (!empty($name)) {
				if ($id = $this->model->addQuiz($name, $desc, $maxfp, $questcount)) {
					$this->func->info('Quiz wurde erfolgreich angelegt!');
					$this->func->go('/?page=quiz&id=' . (int)$id);
				}
			}
		}

		$this->func->addContent($this->view->quizForm());
	}

	public function sessiondetail()
	{
		if ($fs = $this->model->getValues(array('name', 'nachname', 'photo', 'rolle', 'geschlecht', 'sleep_status'), 'foodsaver', $_GET['fsid'])) {
			$this->func->addBread('Quiz Sessions von ' . $fs['name'] . ' ' . $fs['nachname']);
			$this->func->addContent($this->view->topbar('Quiz-Sessions von ' . $fs['name'] . ' ' . $fs['nachname'], $this->getRolle($fs['geschlecht'], $fs['rolle']), $this->func->avatar($fs)), CNT_TOP);

			if ($sessions = $this->model->getUserSessions($_GET['fsid'])) {
				$this->func->addContent($this->view->userSessions($sessions, $fs));
			} else {
				$this->func->addContent($this->view->noSessions($quiz));
			}
		}
	}

	private function getRolle($gender_id, $rolle_id)
	{
		return $this->func->s('rolle_' . $rolle_id . '_' . $gender_id);
	}

	public function sessions()
	{
		if ($quiz = $this->model->getValues(array('id', 'name'), 'quiz', $_GET['id'])) {
			if ($sessions = $this->model->getSessions($_GET['id'])) {
				$this->func->addContent($this->view->sessionList($sessions, $quiz));
			} else {
				$this->func->addContent($this->view->noSessions($quiz));
			}
			$this->func->addBread($quiz['name'], '/?page=quiz&id=' . (int)$_GET['id']);
			$this->func->addBread('Auswertung');
			$slogan = 'Klausurfragen für ' . $quiz['name'];

			$this->func->addContent($this->view->topbar('Auswertung für ' . $quiz['name'] . ' Quiz', $slogan, '<img src="/img/quiz.png" />'), CNT_TOP);
		}
	}

	public function listQuestions($quiz_id)
	{
		$this->func->addContent($this->view->quizbuttons($quiz_id));

		$this->func->addContent($this->view->listQuestions($this->model->listQuestions($quiz_id), $quiz_id));

		$this->func->addContent('<div style="height:15px;"></div>' . $this->view->quizbuttons($quiz_id));
	}
}
