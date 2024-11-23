<?php
declare(strict_types=1);

namespace Visus\CustomerTfa\Controller;

use Laminas\Http\Response;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for generating HTTP responses
 *
 * @since 1.0.0
 */
class ResponseFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private readonly ObjectManagerInterface $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Create response instance with given data
     *
     * Remarks:
     *
     * - If \$httpStatusCode is 204, \$data will be discarded.
     * - If \$data is empty and \$httpStatusCode is 200, it will be set to 204.
     *
     * @param mixed $data
     * @param int $httpStatusCode
     * @return ResultInterface
     */
    public function create(mixed $data = null, int $httpStatusCode = Response::STATUS_CODE_200): ResultInterface
    {
        $data = $httpStatusCode === Response::STATUS_CODE_204 ? null : $data;

        $result = empty($data)
            ? $this->objectManager->create(Raw::class)
            : $this->objectManager->create(Json::class);

        if (empty($data) && $httpStatusCode === Response::STATUS_CODE_200) {
            $result->setHttpResponseCode(Response::STATUS_CODE_204);
        } else {
            $result->setHttpResponseCode($httpStatusCode);
        }

        if ($result instanceof Json && !empty($data)) {
            $result->setData($data);
        }

        return $result;
    }
}
