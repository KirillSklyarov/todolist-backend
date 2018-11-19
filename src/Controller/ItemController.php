<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\User;
use App\Exception\ClassException;
use App\Exception\ValidationException;
use App\Repository\ItemRepository;
use DateTimeZone;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * @return JsonResponse
     * @throws \App\Exception\ClassException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function create(Request $request, ItemRepository $itemRepository)
    {
        // TODO: вставка между существующими
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }
        $now = new \DateTime();
        $inputData = $this->convertJson($request);
        $errors = $this->validateItem($inputData);
        if (\count($errors) > 0) {
            throw new ValidationException($errors, 'Input date error');
        }
        $date = new \DateTime($inputData['date'], new DateTimeZone('UTC'));
        $item = (new Item())
            ->setUser($user)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setTitle($inputData['title'])
            ->setDescription($inputData['description'])
            ->setDate($date)
        ;
        $lastPosition = $itemRepository->getLastPosition($user, $item->getDate());
        $item->setPosition(null === $lastPosition ? 0 : $lastPosition + 1);
        $itemRepository->create($item);

        return new JsonResponse($item->toArray());
    }

    /**
     * @Route("/read/{inputDate}/{count}/{page}", methods={"GET"},
     *     name="item_read_items",
     *     requirements={"count"="\d+", "count"="\d+"})
     * @param string $inputDate
     * @param int $page
     * @param int $count
     * @param ItemRepository $itemRepository
     * @return JsonResponse
     * @throws ClassException
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function readItems(string $inputDate, int $page, int $count,
                              ItemRepository $itemRepository)
    {
        $errors = [];
        $itemsMaxResult = $this->getParameter('items.max.result');
        if ($count > $itemsMaxResult) {
            $errors['count'] = [\sprintf('Count must be less then %s', $itemsMaxResult)];
        }
        try {
            $date = $this->createDate($inputDate);
        } catch (ValidationException $exception) {
            $errors['date'] = [$exception->getMessage()];
        }
        if (\count($errors) > 0) {
            throw new ValidationException('Ошибка данных', $errors);
        }
        if (!isset($date)) {
            throw new \Exception('Var $date does not set');
        }
        $start = $page * $count - $count;
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }
        $items = $itemRepository->findBy(['user' => $user, 'date' => $date],
            ['position' => 'ASC'], $count, $start);
        $result = [];
        foreach ($items as $item) {
            $result[] = $item->toArray(true);
        }
        return new JsonResponse($result);

    }

    public function update()
    {

    }

    /**
     * @Route("/delete/{uuid}", methods={"POST"}, name="item_delete",
     *     requirements={"uuid" = "[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}"})
     * @param string $uuid
     * @return JsonResponse
     * @throws \Exception
     */
    public function delete(string $uuid, ItemRepository $itemRepository, UserInterface $user)
    {
        $uuid = \strtolower($uuid);
        $item = $itemRepository->findOneBy(['uuid' => $uuid, 'user' => $user]);
        if (!$item) {
            throw new NotFoundHttpException(\sprintf('Item uuid %s not found', $uuid));
        }
        $itemRepository->delete($item);

        return new JsonResponse(['success' => true]);
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
        if (false === $patternCheck) {
            throw new \Exception('preg_match error');
        }
        $message = 'Дата не соответствует шаблону';
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
