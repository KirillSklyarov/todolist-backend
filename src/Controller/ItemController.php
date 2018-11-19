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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class ItemController
 * @package App\Controller
 * @Route("/api/v1/item")
 */
class ItemController extends BaseController
{
    /**
     * @param array $input
     * @return ConstraintViolationListInterface
     */
    public static function validate(array $input): ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();
        $collection = [
            'title' => [
                new Assert\Length([
                    'min' => 1,
                    'minMessage' => self::MESSAGE_MIN_LENGHT,
                    'max' => 255,
                    'maxMessage' => self::MESSAGE_MAX_LENGHT
                ])
            ],
            'description' => [
                new Assert\Optional(),
                new Assert\Length([
                    'max' => 4000,
                    'minMessage' => self::MESSAGE_MIN_LENGHT
                ])
            ],
            'date' => [
                new Assert\Date([
                    'message' => self::MESSAGE_DATE
                ])
            ]
        ];
        if (\array_key_exists('position', $input)) {
            $collection['position'] = [
                new Assert\Optional(),
                new Assert\Type([
                    'type' => 'integer',
                    'message' => self::MESSAGE_INTEGER
                ])
            ];
        }
        $constraint = new Assert\Collection($collection);
        $violations = $validator->validate($input, $constraint);

        return $violations;
    }

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
        $errors = self::validate($inputData);
        if (\count($errors) > 0) {
            throw new ValidationException($errors, self::INPUT_DATA_ERROR);
        }
        $date = new \DateTime($inputData['date'], new DateTimeZone('UTC'));
        $item = (new Item())
            ->setUser($user)
            ->setCreatedAt($now)
            ->setUpdatedAt($now)
            ->setTitle($inputData['title'])
            ->setDescription($inputData['description'])
            ->setDate($date);
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
}
