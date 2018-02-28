<?php

namespace Foodsharing\Modules\Mails;

use Flourish\fEmail;
use Flourish\fFile;
use Flourish\fMailbox;
use Flourish\fSMTP;
use Foodsharing\Lib\Db\Mem;
use Foodsharing\Modules\Console\ConsoleControl;
use Foodsharing\Modules\Mailbox\MailboxModel;

class MailsControl extends ConsoleControl
{
	/**
	 * @var fSMTP
	 */
	public static $smtp = false;
	public static $last_connect;
	private $mailboxModel;

	public function __construct(MailsModel $model, MailboxModel $mailboxModel)
	{
		error_reporting(E_ALL);
		ini_set('display_errors', '1');
		self::$smtp = false;
		$this->model = $model;
		$this->mailboxModel = $mailboxModel;
		parent::__construct();
	}

	public function queueWorker()
	{
		while (1) {
			$elem = Mem::$cache->brpoplpush('workqueue', 'workqueueprocessing', 10);
			if ($elem !== false && $e = unserialize($elem)) {
				switch ($e['type']) {
					case 'email':
						$res = $this->handleEmail($e['data']);
						// very basic email rate limit
						usleep(100000);
						break;
					default:
						$res = false;
						break;
				}
				if ($res) {
					Mem::$cache->lrem('workqueueprocessing', $elem, 1);
				} else {
					// TODO handle failed tasks?
				}
			}
		}
	}

	/**
	 * This Method will check for new E-Mails and sort it to the mailboxes.
	 */
	public function mailboxupdate()
	{
		$mailbox = new fMailbox('imap', IMAP_HOST, IMAP_USER, IMAP_PASS);

		$messages = $mailbox->listMessages();
		if (is_array($messages) && count($messages) > 0) {
			self::info(count($messages) . ' in Inbox');

			$progressbar = $this->progressbar(count($messages));

			$have_send = array();
			$i = 0;

			foreach ($messages as $msg) {
				++$i;
				$progressbar->update($i);
				if ($message = $mailbox->fetchMessage((int)$msg['uid'])) {
					$mboxes = array();
					if (isset($message['headers']) && isset($message['headers']['to'])) {
						foreach ($message['headers']['to'] as $to) {
							if (strtolower($to['host']) == DEFAULT_HOST) {
								$mboxes[] = $to['mailbox'];
							}
						}
						if (isset($message['headers']['cc'])) {
							foreach ($message['headers']['cc'] as $to) {
								if (strtolower($to['host']) == DEFAULT_HOST) {
									$mboxes[] = $to['mailbox'];
								}
							}
						}
						if (isset($message['headers']['bcc'])) {
							foreach ($message['headers']['cc'] as $to) {
								if (strtolower($to['host']) == DEFAULT_HOST) {
									$mboxes[] = $to['mailbox'];
								}
							}
						}

						if (empty($mboxes)) {
							$mailbox->deleteMessages((int)$msg['uid']);
							continue;
						}

						$mb_ids = $this->model->getMailboxIds($mboxes);

						if (!$mb_ids) {
							$mb_ids = $this->model->getMailboxIds(array('lost'));
						}

						if ($mb_ids) {
							$html = '';
							if (isset($message['html'])) {
								$h2t = new \Html2Text\Html2Text($message['html']);
								$body = $h2t->get_text();
								$html = $message['html'];
								$html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
							} elseif (isset($message['text'])) {
								$body = $message['text'];
								$html = nl2br($this->func->autolink($message['text']));
							} else {
								$body = json_encode($message);
							}

							$attach = '';
							if (isset($message['attachment']) && !empty($message['attachment'])) {
								$attach = array();
								foreach ($message['attachment'] as $a) {
									if ($this->attach_allow($a['filename'], $a['mimetype'])) {
										$new_filename = uniqid();
										$path = 'data/mailattach/';
										while (file_exists($path . $new_filename)) {
											++$i;
											$new_filename = $i . '-' . $a['filename'];
										}

										file_put_contents($path . $new_filename, $a['data']);
										$attach[] = array(
											'filename' => $new_filename,
											'origname' => $a['filename'],
											'mime' => $a['mimetype']
										);
									}
								}
								$attach = json_encode($attach);
							}

							foreach ($mb_ids as $id) {
								if (!isset($have_send[$id])) {
									$have_send[$id] = array();
								}
								$md = $message['received'] . ':' . $message['headers']['subject'];
								if (!isset($have_send[$id][$md])) {
									$have_send[$id][$md] = true;
									$this->model->saveMessage(
										$id, // mailbox id
										1, // folder
										json_encode($message['headers']['from']), // sender
										json_encode($message['headers']['to']), // to
										strip_tags($message['headers']['subject']), // subject
										$body,
										$html,
										date('Y-m-d H:i:s', strtotime($message['received'])), // time,
										$attach, // attachements
										0,
										0
									);
								}
							}
						}
					}
				}

				$mailbox->deleteMessages((int)$msg['uid']);
			}
			echo "\n";
			self::success('ready :o)');
		}
	}

	private function attach_allow($filename, $mime)
	{
		if (strlen($filename) < 300) {
			$ext = explode('.', $filename);
			$ext = end($ext);
			$ext = strtolower($ext);
			$notallowed = array(
				'php' => true,
				'html' => true,
				'htm' => true,
				'php5' => true,
				'php4' => true,
				'php3' => true,
				'php2' => true,
				'php1' => true
			);
			$notallowed_mime = array();

			if (!isset($notallowed[$ext]) && !isset($notallowed_mime[$mime])) {
				return true;
			}
		}

		return false;
	}

	public function handleEmail($data)
	{
		self::info('mail arrived ...: ' . $data['from'][0] . '@' . $data['from'][1]);
		$email = new fEmail();
		$email->setFromEmail($data['from'][0], $data['from'][1]);
		$email->setSubject($data['subject']);
		$email->setHTMLBody($data['html']);
		$email->setBody($data['body']);

		if (!empty($data['attachments'])) {
			foreach ($data['attachments'] as $a) {
				$file = new fFile($a[0]);

				// only files smaller 10 MB
				if ($file->getSize() < 1310720) {
					$email->addAttachment($file, $a[1]);
				}
			}
		}
		$has_recip = false;
		foreach ($data['recipients'] as $r) {
			// check is it own lmr email? put direct into db
			$r[0] = strtolower($r[0]);
			self::info(substr(
				$r[0],
				(strlen(DEFAULT_HOST) * -1),
				strlen(DEFAULT_HOST)
			));
			if (
				substr(
					$r[0],
					(strlen(DEFAULT_HOST) * -1),
					strlen(DEFAULT_HOST)
				) == DEFAULT_HOST
			) {
				self::info($r[0] . ' own host save direct into db');

				$mailbox = str_replace('@' . DEFAULT_HOST, '', $r[0]);

				$mb_id = $this->model->getMailboxId($mailbox);
				if (!$mb_id) {
					// lost mailbox id
					$mb_id = 25631;
				}

				$toarr = array();
				foreach ($data['recipients'] as $r) {
					$toarr[] = self::parseEmailAddress($r[0], $r[1]);
				}

				$this->model->saveMessage(
					$mb_id, // mailbox id
					1, // folder inbox
					json_encode(self::parseEmailAddress($data['from'][0], $data['from'][1])), // sender
					json_encode($toarr), // to
					$data['subject'], // subject
					$data['body'],
					$data['html'],
					date('Y-m-d H:i:s') // time,
				);
			} else {
				$email->addRecipient($r[0], $r[1]);
				$has_recip = true;
			}
		}
		if (!$has_recip) {
			return true;
		}

		// reconnect first time and force after 60 seconds inactive
		if (self::$smtp === false || (time() - self::$last_connect) > 60) {
			self::smtpReconnect();
		}

		$max_try = 3;
		$sended = false;
		while (!$sended) {
			--$max_try;
			try {
				self::info('send email tries remaining ' . ($max_try));
				$email->send(self::$smtp);
				self::success('email send OK');

				// remove atachements from temp folder
				if (!empty($data['attachments'])) {
					foreach ($data['attachments'] as $a) {
						@unlink($a[0]);
					}
				}

				return true;
				$sended = true;
				break;
			} catch (\Exception $e) {
				self::smtpReconnect();
				self::error('email send error: ' . $e->getMessage());
				self::error(print_r($data, true));
			}

			if ($max_try == 0) {
				return false;
				break;
			}
		}

		return true;
	}

	public static function parseEmailAddress($email, $name = false)
	{
		$p = explode('@', $email);

		if ($name === false) {
			$name = $email;
		}

		return array(
			'personal' => $name,
			'mailbox' => $p[0],
			'host' => $p[1]
		);
	}

	/**
	 * checks current status and renew the connection to smtp server.
	 */
	public static function smtpReconnect()
	{
		self::info('SMTP reconnect.. ');
		try {
			if (self::$smtp !== false) {
				self::info('close smtp and sleep 5 sec ...');
				@self::$smtp->close();
				//sleep(5);
			}

			self::info('connect...');
			self::$smtp = new fSMTP(SMTP_HOST, SMTP_PORT);
			//MailsControl::$smtp->authenticate(SMTP_USER, SMTP_PASS);
			self::$last_connect = time();

			self::success('reconnect OK');

			return true;
		} catch (\Exception $e) {
			self::error('reconnect failed: ' . $e->getMessage());

			return false;
		}

		return true;
	}
}
