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

class ApiResponse extends JsonResponse
{
    /**
     * ApiResponse constructor.
     * @param mixed $data
     * @param Error|null $error
     * @param bool $success
     * @param int $status
     * @param array $headers
     * @param bool $json
     * @throws ClassException
     */
    public function __construct($data = null,
                                ?Error $error = null,
                                bool $success = true,
                                array $headers = array())
    {
        $apiData = [
            'success' => $success,
            'error' => $error ? $error->toArray() : null,
            'data' => $data
        ];
        parent::__construct($apiData, 200, $headers, false);
    }

    /**
     * @param array|null $data
     * @param Error|null $error
     * @param bool $success
     * @return ApiResponse
     * @throws ClassException
     */
    public function setApiData(?array $data = null,
                               ?Error $error = null,
                               bool $success = true)
    {
        $apiData = [
            'success' => $success,
            'error' => $error ? $error->toArray() : null,
            'data' => $data
        ];
        return $this->setData($apiData);
    }
}