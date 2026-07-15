<?php

/**
 * A trait that allows getting error messages
 * The class using this trait must also implement ServiceLocatorAwareInterface
 *
 * @package     olcscommon
 * @subpackage  utility
 * @author      Pele Odiase <pele.odiase@valtech.co.uk>
 */

namespace CpmsCommon\Utility;

use CpmsCommon\Service\ErrorCodeService;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Stdlib\Parameters;
use Laminas\ServiceManager\ServiceManager;
use Psr\Log\LoggerInterface;

/**
 * Class Error CodeAwareTrait
 * The class using this trait MUST implement the ServiceLocatorAware interface
 * @method ServiceManager getServiceLocator()
 *
 * @package CpmsCommon\Utility
 */
trait ErrorCodeAwareTrait
{
    /**
     * Return an array with error code and message
     *
     * @param int $errorCode
     * @param array $replacement
     * @param ?int $httpStatusCode
     * @param array $data
     *
     * @return array
     */
    public function getErrorMessage($errorCode, $replacement = [], $httpStatusCode = null, $data = array())
    {

        $replacement = (array)$replacement;
        $container = $this->getServiceLocator();
        /** @var ErrorCodeService $errorService */
        $errorService = $container->get('cpms/errorCodeService');

        $httpStatusCode = empty($httpStatusCode) ? Response::STATUS_CODE_400 : $httpStatusCode;
        $message = $errorService->getErrorMessage($errorCode, $replacement, $httpStatusCode, $data);

        if ($this->getServiceLocator()->has('logger')) {
            /** @var LoggerInterface $logger */
            $logger = $this->getServiceLocator()->get('logger');
            $logger->debug(print_r($message, true));
            if (isset($message['code']) and $message['code'] == ErrorCodeService::GENERIC_ERROR_CODE) {
                /** @var Request $request */
                $request = $this->getServiceLocator()->get('request');

                if ($request instanceof Request) {
                    /** @var Parameters $server */
                    $server = $request->getServer();
                    $debugInfo = [
                        'server' => $server->getArrayCopy(),
                        'request' => $request->toString(),
                    ];
                    /** @var Parameters $query */
                    $query = $request->getQuery();
                    /** @var Parameters $post */
                    $post = $request->getPost();
                    $debugInfo['getParams'] = $query->getArrayCopy();
                    $debugInfo['postParams'] = $post->getArrayCopy();
                    /** @var LoggerInterface $logger */
                    $logger = $this->getServiceLocator()->get('logger');
                    $logger->debug(print_r($debugInfo, true));
                }
            }
        }

        return $message;
    }

    /**
     * Get success message
     *
     * @param array $data
     *
     * @return array
     */
    public function getSuccessMessage(array $data = null)
    {
        /** @var ErrorCodeService $errorService */
        $errorService = $this->getServiceLocator()->get('cpms\errorCodeService');

        return $errorService->getSuccessMessage($data);
    }

    /**
     * Get error message
     *
     * @param int $code
     * @param string $replacement
     *
     * @return string
     */
    public function getMessage($code, $replacement = '')
    {
        /** @var ErrorCodeService $errorService */
        $errorService = $this->getServiceLocator()->get('cpms\errorCodeService');

        return $errorService->getMessage($code, [$replacement]);
    }
}
