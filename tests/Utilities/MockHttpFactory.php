<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Utilities;

use Joomla\Http\HttpInterface;
use Joomla\Http\Response;

/**
 * Factory for creating mock HTTP clients for testing
 *
 * This allows tests to simulate various HTTP responses without making
 * actual network requests.
 */
class MockHttpFactory
{
    /**
     * Create a mock HTTP client that returns a specific response for GET requests
     *
     * @param int    $code    HTTP status code
     * @param string $body    Response body
     * @param array<string, string|array<string>>  $headers Response headers
     */
    public static function createWithGetResponse(int $code, string $body = '', array $headers = []): HttpInterface
    {
        return new class ($code, $body, $headers) implements HttpInterface {
            /**
             * @param array<string, string|array<string>> $headers
             */
            public function __construct(
                private readonly int $code,
                private readonly string $body,
                private readonly array $headers,
            ) {}

            public function get(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, $this->body, $this->headers);
            }

            public function head(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, '', $this->headers);
            }

            public function post(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }

            public function put(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }

            public function delete(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }

            public function patch(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }
        };
    }

    /**
     * Create a mock HTTP client that returns a specific response for HEAD requests
     * Used for checks that only fetch headers (like ServerTimeCheck)
     *
     * @param int   $code    HTTP status code
     * @param array<string, string|array<string>> $headers Response headers
     */
    public static function createWithHeadResponse(int $code, array $headers = []): HttpInterface
    {
        return new class ($code, $headers) implements HttpInterface {
            /**
             * @param array<string, string|array<string>> $headers
             */
            public function __construct(
                private readonly int $code,
                private readonly array $headers,
            ) {}

            public function get(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, '', $this->headers);
            }

            public function head(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response($this->code, '', $this->headers);
            }

            public function post(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }

            public function put(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }

            public function delete(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }

            public function patch(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                return new Response(200, '', []);
            }
        };
    }

    /**
     * Create a mock HTTP client that throws an exception (simulates network failure)
     *
     * @param string $message Exception message
     */
    public static function createThatThrows(string $message = 'Network error'): HttpInterface
    {
        return new class ($message) implements HttpInterface {
            public function __construct(
                private readonly string $message,
            ) {}

            public function get(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function head(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function post(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function put(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function delete(string $url, array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }

            public function patch(string $url, $data = '', array $headers = [], int|float $timeout = 10): Response
            {
                throw new \RuntimeException($this->message);
            }
        };
    }

    /**
     * Create a mock HTTP client with JSON API response
     *
     * @param int          $code HTTP status code
     * @param array<mixed> $data Data to encode as JSON
     */
    public static function createWithJsonResponse(int $code, array $data): HttpInterface
    {
        return self::createWithGetResponse($code, json_encode($data) ?: '[]', [
            'Content-Type' => 'application/json',
        ]);
    }
}
