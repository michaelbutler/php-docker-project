<?php

declare(strict_types=1);

/*
 *   This file is part of php-docker-project
 *   Source: https://github.com/michaelbutler/php-docker-project
 *
 *   THIS HEADER MESSGAGE MAY BE MODIFIED IN .php-cs-fixer.dist.php
 *   in the project root folder.
 *
 *   (c) 2022-23 foo-example.com
 */

namespace MyApp;

use DI\Container;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use MyApp\console\HelloWorld;
use MyApp\controller\HelloWorldController;
use MyApp\helper\CookieHelper;
use MyApp\middleware\CustomErrorMiddleware;
use MyApp\middleware\SubdomainMiddleware;
use MyApp\service\Cache;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Symfony\Component\Console\Application;

/**
 * Basic PHP web app.
 */
class App
{
    public static bool $is_dev = false;

    /** @var \Slim\App */
    private $slim;

    public function __construct(protected array $config) {}

    /**
     * @param string $value Key of the config to get
     */
    public function getConfig(string $value): mixed
    {
        if ('' === $value) {
            throw new \InvalidArgumentException('Must pass non-empty string for getConfig');
        }

        return $this->config[$value];
    }

    /**
     * Bootstrap operations for all environments, CLI and web based.
     *
     * @param string $displayErrors Setting for displayErrors (should be 0 or 1 as string)
     */
    public function bootstrapCommon(string $displayErrors): Container
    {
        if ('1' === getEnvValue('IS_DEV')) {
            self::$is_dev = true;
            $logLevel = Logger::DEBUG;
        } else {
            self::$is_dev = false;
            $logLevel = Logger::INFO;
        }
        ini_set('display_errors', $displayErrors);

        // Our custom Container class that wraps the DI\Container
        $container = new \DI\Container();

        AppFactory::setContainer($container);
        $app = AppFactory::create();

        $this->slim = $app;

        $logger = new Logger('app');

        // This will log according to php.ini setting for error_log()
        $errorLogHandler = new ErrorLogHandler(0, $logLevel);
        $logger->pushHandler($errorLogHandler);

        $container->set('logger', $logger);

        $container->set('cache', function () {
            return new Cache(
                getEnvValue('REDIS_HOST'),
                getEnvValue('REDIS_PORT'),
                0.3,
                getEnvValue('REDIS_USERNAME'),
                getEnvValue('REDIS_PASSWORD')
            );
        });

        // Example of registering/injecting a custom service
        // $container->set('email', function () {
        //     $apiUsername = getEnvValue('EMAIL_API_USERNAME');
        //     $apiToken = getEnvValue('EMAIL_API_KEY');

        //     return new EmailService($apiUsername, $apiToken);
        // });

        return $container;
    }

    /**
     * Run bootstrap and environment set up required for running console commands.
     */
    public function bootstrapCli(): void
    {
        $this->bootstrapCommon('1');
    }

    /**
     * Add possible console commands and run them based on input CLI arguments.
     *
     * @throws \Exception
     */
    public function registerCommands(): Application
    {
        $application = new Application();
        $application->add(new HelloWorld());

        return $application;
    }

    /**
     * Run the CLI App.
     *
     * @throws \Exception
     */
    public function runCommands(Application $app): int
    {
        return $app->run();
    }

    /**
     * Run the web request app synchronously; serves a web request and returns.
     */
    public function runWeb(): void
    {
        $this->registerShutdownWeb();

        $container = $this->bootstrapCommon('0');

        $container->set('cookies', function () {
            return new CookieHelper();
        });

        $app = $this->slim;

        // Register Middlewares
        $app->addRoutingMiddleware();
        $app->add(new SubdomainMiddleware($container));
        // Must be added last in the chain
        $app->add(new CustomErrorMiddleware($container, App::$is_dev));

        // Handle a GET request
        $app->get('/', [HelloWorldController::class, 'actionHelloWorld']);

        // Handle a POST request
        // $app->post('/some/endpoint', [HelloWorldController::class, 'actionPostedData']);

        $app->run();
    }

    public function getSlim(): \Slim\App
    {
        return $this->slim;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->getSlim()->getContainer()->get('logger');
    }

    /**
     * Register a shutdown function that runs when the SERVER shuts down, for example after the web request was served.
     */
    protected function registerShutdownWeb(): void
    {
        $myApp = $this;
        register_shutdown_function(function () use ($myApp) {
            $app = $myApp;

            /** @var null|\Monolog\Logger $logger */
            $logger = $app->getSlim()->getContainer() ? $app->getSlim()->getContainer()->get('logger') : null;

            // Add an access log type line to the logs.

            /**
             * @psalm-suppress UndefinedConstant
             */
            $duration = number_format(1000 * (microtime(true) - REQUEST_TIME_START), 1, '.', '') . 'ms';
            $message = 'AccessLog:' . "\t";
            $message .= $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . "\t";

            $respCode = http_response_code();
            $message .= ($respCode ?: '000') . "\t";
            $message .= $_SERVER['REMOTE_ADDR'] . "\t";
            $message .= substr($_SERVER['HTTP_USER_AGENT'], 0, 130) . "\t";
            $message .= $duration;

            if ($logger) {
                $logger->log(Logger::INFO, $message);
            } else {
                error_log($message);
            }

            $error = error_get_last();
            if (empty($error)) {
                return;
            }

            if (!CustomErrorMiddleware::$didLogError) {
                CustomErrorMiddleware::$didLogError = true;

                // Log it
                $err_log = \json_encode($error);

                if ($logger) {
                    $logger->log(Logger::ERROR, $err_log);
                } else {
                    error_log($err_log);
                }
            }

            // Render it to the browser
            $exception = new \RuntimeException($error['message'], 555);
            outputExceptionToHttp($app, $exception);
        });
    }
}
