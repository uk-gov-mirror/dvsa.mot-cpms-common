<?php

namespace CpmsCommonTest\Controller;

use CpmsCommon\Service\ErrorCodeService;
use CpmsCommon\Service\LoggerService;
use CpmsCommon\View\JsonExceptionStrategy;
use CpmsCommonTest\Bootstrap;
use CpmsCommonTest\SampleController;
use Laminas\Authentication\Adapter\Exception\RuntimeException;
use Laminas\Http\Header\ContentType;
use Laminas\Http\Headers;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\TreeRouteStack as HttpRouter;
use Laminas\Router\RouteMatch;
use Laminas\Mvc\Controller\ControllerManager;
use Laminas\ServiceManager\ServiceManager;

/**
 * Class SampleControllerTest
 *
 * @package CpmsCommonTest\Controller
 */
class SampleControllerTest extends AbstractHttpControllerTestCase
{
    protected SampleController $controller;

    protected ServiceManager $serviceManager;

    protected MvcEvent $event;

    protected RouteMatch $routeMatch;

    protected Request $request;

    public function setUp(bool $noConfig = false): void
    {

        $this->setApplicationConfig(
            include __DIR__ . '/../../../' . 'config/application.config.php'
        );

        $this->serviceManager = Bootstrap::getInstance()->getServiceManager();
        /** @var array $applicationConfig */
        $applicationConfig = $this->serviceManager->get('ApplicationConfig');
        $this->setApplicationConfig($applicationConfig);
        /** @var ControllerManager $controllerManager */
        $controllerManager = $this->serviceManager->get('ControllerManager');
        /** @var SampleController $controller */
        $controller = $controllerManager->get('CpmsCommonTest\Sample');

        $this->controller = $controller;
        $this->controller->setServiceLocator($this->serviceManager);

        $this->request = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'export'));
        $this->event = new MvcEvent();

        /** @var array $config */
        $config = $this->serviceManager->get('Config');

        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);

        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($this->serviceManager);

        $this->controller->setCustomContentType('application/custom.v1');

        parent::setUp(true);
    }

    public function testControllerInstance(): void
    {
        $this->assertInstanceOf('CpmsCommon\Controller\AbstractRestfulController', $this->controller);
        $this->assertInstanceOf(LoggerService::class, $this->controller->getLogger());
        $this->assertInstanceOf('Laminas\ServiceManager\ServiceManager', $this->controller->getServiceLocator());

        $message = $this->controller->getErrorMessage(ErrorCodeService::AN_ERROR_OCCURRED);
        $this->assertTrue(is_array($message));
        $this->assertArrayHasKey('code', $message);

        $this->controller->logException(new \Exception('phpunit'));
        $this->controller->log('phpunit', LoggerService::DEBUG);
    }

    public function testDispatch(): void
    {
        $request = $this->request;
        $headers = new Headers();
        $headers->addHeader(ContentType::fromString('Content-Type: application/json'));
        $headers->addHeaderLine('Accept: application/json');
        $request->setHeaders($headers);

        $request->setUri('/cpms-common-test/5');
        $request->setMethod('get');

        $this->routeMatch->setParam('id', 5);
        $this->routeMatch->setParam('controller', 'SampleController');

        $this->controller->dispatch($request);
        $this->controller->getResponse();

        $this->assertResponseStatusCode(200);
    }

    public function testDispatchWithRedirect(): void
    {
        $request = $this->request;
        $headers = new Headers();
        $headers->addHeader(ContentType::fromString('Content-Type: application/json'));
        $headers->addHeaderLine('Accept: application/json');
        $request->setHeaders($headers);

        $request->setUri('/cpms-common-test');
        $request->setMethod('get');

        $this->routeMatch->setParam('controller', 'SampleController');

        $this->controller->dispatch($request);
        $response = $this->controller->getResponse();

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test405Dispatch(): void
    {
        $request = $this->request;
        $headers = new Headers();
        $headers->addHeader(ContentType::fromString('Content-Type: application/json'));
        $headers->addHeaderLine('Accept: application/json');
        $request->setHeaders($headers);

        $request->setUri('/cpms-common-test');
        $request->setMethod('POST');

        $this->routeMatch->setParam('controller', 'SampleController');

        $this->controller->dispatch($request);
        $this->controller->getResponse();

        $res = $this->controller->getResponse();
        $this->assertSame(405, $res->getStatusCode());
    }

    public function testDispatchException(): void
    {
        $request = $this->request;
        $headers = new Headers();
        $headers->addHeader(ContentType::fromString('Content-Type: application/json'));
        $headers->addHeaderLine('Accept: application/json');
        $request->setHeaders($headers);

        $request->setUri('/cpms-common-test');
        $request->setMethod('POST');

        $this->routeMatch->setParam('controller', 'SampleController');

        $this->controller->dispatch($request);
        $res = $this->controller->getResponse();
        /** @var string $responseContent */
        $responseContent = $res->getContent();
        $content = \json_decode($responseContent, true);

        $this->assertTrue(is_array($content));
        $this->assertArrayHasKey('code', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertSame(ErrorCodeService::NOT_IMPLEMENTED, $content['code']);
        $this->assertSame(405, $res->getStatusCode());
    }

    public function testExceptionStrategy(): void
    {
        $request = $this->request;
        $headers = new Headers();
        $headers->addHeader(ContentType::fromString('Content-Type: application/json'));
        $headers->addHeaderLine('Accept: application/json');
        $request->setHeaders($headers);

        $request->setUri('/cpms-common-test/5');
        $request->setMethod('PUT');

        $this->routeMatch->setParam('id', 5);
        $this->routeMatch->setParam('controller', 'SampleController');

        $this->controller->dispatch($request);

        $event = $this->controller->getEvent();
        $event->setApplication($this->getApplication());
        $strategy = new JsonExceptionStrategy();
        $strategy->prepareExceptionViewModel($event);

        $event->setError(Application::ERROR_EXCEPTION);
        $event->setParam('exception', new RuntimeException('PHP tests'));
        $strategy->prepareExceptionViewModel($event);

        $event->setResponse(new \Laminas\Stdlib\Response());
        $strategy->setDisplayExceptions(true);
        $strategy->prepareExceptionViewModel($event);

        /** @var Response $res */
        $res = $event->getResponse();
        $this->assertSame(500, $res->getStatusCode());

        $event->setError(Application::ERROR_ROUTER_NO_MATCH);
        $strategy->prepareExceptionViewModel($event);

        $event->setResult($event->getResponse());
        $strategy->prepareExceptionViewModel($event);
    }

    public function testGetMessage(): void
    {
        $request = new Request();
        $controller = clone $this->controller;
        $controller->getServiceLocator()->setService('request', $request);
        $message = $this->controller->getErrorMessage(ErrorCodeService::GENERIC_ERROR_CODE, [0]);
        $this->assertNotEmpty($message);
    }
}
