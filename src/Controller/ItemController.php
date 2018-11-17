<?php

namespace App\Controller;

use App\Entity\Item;
use App\Exception\ValidationException;
use App\Repository\ItemRepository;
use DateTimeZone;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ItemController
 * @package App\Controller
 * @Route("/api/v1/item")
 */
class ItemController extends BaseController
{
    const DATE_PATTERN = '#^\d{4}-\d{2}-\d{2}#';
    /**
     * @Route("/create", methods={"POST"}, name="item_create")
     * @param Request $request
     * @param ItemRepository $itemRepository
     * @return void
     * @throws \App\Exception\ClassException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function create(Request $request, ItemRepository $itemRepository)
    {
        $now = new \DateTime();
        $errors = [];
        $inputData = $this->convertJson($request);
        if (!(\property_exists($inputData, 'title')
            && 'string' === \gettype($inputData->title))) {
            $errors['title'] = ['Поле title должно присутствовать и иметь тип string'];
        }
        if (!(property_exists($inputData, 'description')
            && 'string' === \gettype($inputData->description))) {
            $errors['description'] = ['Поле description должно присутствовать и иметь тип string'];
        }
        if (property_exists($inputData, 'position') &&
            !('integer' === \gettype($inputData->position) ||
            'NULL' === \gettype($inputData->position))
        ) {
            $errors['position'] = ['Поле position должно присутствовать и иметь тип integer или null'];
        }
        $item = new Item();
        try {
            $date = $this->createDate($inputData->date);
            $item->setDate($date);
        } catch (ValidationException $exception) {
            $errors['date'] = $exception->getMessage();
        }

        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }
        $item = (new Item())
            ->setCreatedAt($now)
//            ->setDate()
        ;
    }

    /**
     * @param string $inputDate
     * @return \DateTime
     * @throws ValidationException
     * @throws \Exception
     */
    private function createDate(string $inputDate)
    {
        $patternCheck = \preg_match(self::DATE_PATTERN, $inputDate);
        $message = 'Дата не соответствует шаблону';
        if (false === $patternCheck) {
            throw new \Exception('preg_match error');
        }
        if (0 === $patternCheck) {
            throw new ValidationException($message);
        }
        try {
            $date = new \DateTime($inputDate, new DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            throw new ValidationException($message);
        }
        return $date;
    }
}
