<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 23.11.2018
 * Time: 18:28
 */

namespace App\Model;


use App\Exception\ClassException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse extends JsonResponse
{
    /**
     * @var array|null
     */
    private $apiData;

    /**
     * @var Error|null
     */
    private $error;

    /**
     * @var bool
     */
    private $success;

    /**
     * ApiResponse constructor.
     * @param array|null $apiData
     * @param Error|null $error
     * @param bool $success
     * @param int $status
     * @param array $headers
     */
    public function __construct(?array $apiData = null,
                                ?Error $error = null,
                                bool $success = true,
                                int $status = 200,
                                array $headers = array())
    {
        parent::__construct("null", $status, $headers, true);

        $this->apiData = $apiData;
        $this->error = $error;
        $this->success = $success;
    }

    /**
     * @return array|null
     */
    public function getApiData(): ?array
    {
        return $this->apiData;
    }

    /**
     * @param array|null $apiData
     * @return ApiResponse
     */
    public function setApiData(?array $apiData): ApiResponse
    {
        $this->apiData = $apiData;
        return $this;
    }

    /**
     * @return Error|null
     */
    public function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * @param Error|null $error
     * @return ApiResponse
     */
    public function setError(?Error $error): ApiResponse
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return ApiResponse
     */
    public function setSuccess(bool $success): ApiResponse
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return string
     * @throws ClassException
     */
    public function getContent(): string
    {
        $this->setData($this->toArray());
        return parent::getContent();
    }

    /**
     * @return Response
     * @throws ClassException
     */
    public function sendContent()
    {
        $this->setData($this->toArray());
        return parent::sendContent();
    }

    /**
     * @return array
     * @throws ClassException
     */
    public function toArray(): array {
        return [
            'success' => $this->success,
            'error' => $this->error ? $this->error->toArray() : null,
            'data' => $this->apiData
        ];
    }
}