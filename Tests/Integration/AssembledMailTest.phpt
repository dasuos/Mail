<?php
declare(strict_types = 1);

namespace Dasuos\Mail;

function mail(
	string $to,
	string $subject,
	string $message,
	string $headers = '',
	string $parameters = ''
) {
	printf(
		"To: %s \n Subject: %s \n Message: %s \n Headers: %s \n Parameters: %s",
		$to,
		$subject,
		$message,
		$headers,
		$parameters
	);
	return true;
}

use Dasuos\Mail\Misc\ExemplaryHeaders;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 * @phpVersion > 7.1
 */

final class AssembledMailTest extends \Tester\TestCase {

	public function testReturningToHeader() {
		ob_start();
		(new AssembledMail('from@test.cz', AssembledMail::HIGH_PRIORITY))->send(
			'foo@bar.cz',
			'foo',
			new FakeMessage('message', [])
		);
		$result = ob_get_clean();
		Assert::contains('To: foo@bar.cz', $result);
	}

	public function testReturningSubjectHeaderWithDiacritics() {
		ob_start();
		(new AssembledMail('from@test.cz', AssembledMail::HIGH_PRIORITY))->send(
			'foo@bar.cz',
			'Předmět',
			new FakeMessage('message', [])
		);
		$result = ob_get_clean();
		Assert::contains('Subject: =?UTF-8?B?UMWZZWRtxJt0?=', $result);
	}

	public function testReturningHeadersWithHtmlContentType() {
		ob_start();
		(new AssembledMail('from@bar.cz', AssembledMail::HIGH_PRIORITY))->send(
			'to@bar.cz',
			'foo',
			new HtmlMessage('<h1>foo</h1><p>bar</p>')
		);
		$result = ob_get_clean();
		Assert::contains(
			preg_replace(
				'~\s+~',
				' ',
				'Headers: 
				MIME-Version: 1.0
				From: from@bar.cz
				Return-Path: from@bar.cz
				Date: example
				X-Sender: from@bar.cz
				X-Mailer: PHP/example
				X-Priority: 1
				Message-Id: example
				Content-Type: multipart/alternative; boundary="example"'
			),
			(string) new ExemplaryHeaders($result)
		);
	}

	public function testThrowingOnInvalidSenderEmail() {
		Assert::exception(
			function() {
				(new AssembledMail(
					'invalid',
					AssembledMail::HIGH_PRIORITY
				))->send(
					'foo@bar.cz',
					'foo',
					new FakeMessage('message', [])
				);
			},
			\UnexpectedValueException::class,
			'Invalid sender email'
		);
	}

	public function testThrowingOnInvalidReceiverEmail() {
		Assert::exception(
			function() {
				(new AssembledMail(
					'foo@bar.cz',
					AssembledMail::HIGH_PRIORITY
				))->send(
					'invalid',
					'foo',
					new FakeMessage('message', [])
				);
			},
			\UnexpectedValueException::class,
			'Invalid receiver email'
		);
	}

	public function testThrowingOnInvalidPriority() {
		Assert::exception(
			function() {
				(new AssembledMail(
					'foo@bar.cz',
					123
				))->send(
					'bar@foo.cz',
					'foo',
					new FakeMessage('message', [])
				);
			},
			\UnexpectedValueException::class,
			'Allowed mail priority types are: 5, 3, 1'
		);
	}
}

(new AssembledMailTest())->run();
