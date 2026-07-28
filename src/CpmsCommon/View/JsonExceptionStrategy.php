<?php

/**
 * Generates JSON responses on exceptions
 *
 * @package     olcscommon
 * @subpackage  view
 * @author      Pelle Wessman <pelle.wessman@valtech.se>
 */

namespace CpmsCommon\View;

use CpmsCommon\Service\ErrorCodeService;
use CpmsCommon\Service\LoggerService;
use Laminas\Http\Response;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\View\Http\ExceptionStrategy;
use Laminas\View\Model\JsonModel;

/**
 * Class JsonExceptionStrategy
 *
 * @package CpmsCommon\View
 */
class JsonExceptionStrategy extends ExceptionStrategy
{
    /**
     * Create an exception view model, and set the HTTP status code
     *         dispatch.error does not halt dispatch unless a response is
     *         returned. As such, we likely need to trigger rendering as a low
     *         priority dispatch.error event (or goto a render event) to ensure
     *         rendering occurs, and that munging of view models occurs when
     *         expected.
     *
     * @param  MvcEvent $event
     *
     * @return null
     */
    public function prepareExceptionViewModel(MvcEvent $event): null
    {
        $omittedErrors = array(
            Application::ERROR_CONTROLLER_NOT_FOUND,
            Application::ERROR_CONTROLLER_INVALID,
            Application::ERROR_ROUTER_NO_MATCH
        );
        // Do nothing if no error in the event
        $error = $event->getError();
        if (empty($error)) {
            return null;
        }

        // Do nothing if the result is a response object
        $result = $event->getResult();
        if ($result instanceof Response) {
            return null;
        }

        if (in_array($error, $omittedErrors)) {
            $this->handleException($event, ErrorCodeService::INVALID_ENDPOINT, Response::STATUS_CODE_404);
        } else {
            $this->handleException($event);
        }

        return null;
    }

    /**
     * @param MvcEvent $event
     * @param ?int    $errorCode
     * @param ?int     $statusCode
     */
    private function handleException(MvcEvent $event, $errorCode = null, $statusCode = null): void
    {

        $errorCode  = $errorCode ?: ErrorCodeService::UNKNOWN_ERROR;
        $statusCode = $statusCode ?: Response::STATUS_CODE_500;
        $data       = $this->getResponseData($event, $errorCode, $statusCode);

        $model = new JsonModel($data);
        $model->setTerminal(true);
        $event->setResult($model);
        /** @var HttpResponse $response */
        $response = $event->getResponse();

        if (!$response instanceof HttpResponse) {
            $response = new HttpResponse();
            $response->setStatusCode($statusCode);
            $event->setResponse($response);
        } else {
            if ($response->getStatusCode() === 200) {
                $response->setStatusCode($statusCode);
            }
        }
        $event->setResponse($response);
    }

    /**
     * @param MvcEvent $event
     * @param int $errorCode
     * @param ?int $statusCode
     *
     * @return array
     */
    private function getResponseData(MvcEvent $event, $errorCode, $statusCode): array
    {
        $data = array();
        /** @var ?\Exception $exception */
        $exception = $event->getParam('exception');
        if ($exception) {
            /** @phpstan-ignore if.alwaysTrue */
            if ($app = $event->getApplication()) {
                $serviceLocator = $app->getServiceManager();
                /** @var ErrorCodeService $errorService */
                $errorService = $serviceLocator->get('cpms\errorCodeService');
                $data = $errorService->getErrorMessage($errorCode);
                /** @var LoggerService $logger */
                $logger = $serviceLocator->get('Logger');
                $logger->error($exception->getMessage(), ['ex' => $exception]);
            } else {
                $data = array(
                    ErrorCodeService::ERROR_CODE_KEY    => ErrorCodeService::CRITICAL_ERROR,
                    ErrorCodeService::ERROR_MESSAGE_KEY => ErrorCodeService::UNKNOWN_ERROR_MESSAGE,
                    ErrorCodeService::HTTP_STATUS_KEY   => $statusCode
                );
            }
        }

        return $data;
    }
}
