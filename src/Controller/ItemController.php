<?php

namespace App\Controller;

use App\Entity\Item;
use App\Entity\User;
use App\Exception\ClassException;
use App\Exception\ValidationException;
use App\Model\ApiResponse;
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
    public function validatePost(array $input): ConstraintViolationListInterface
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

    public function validateGet(array $input)
    {
        $validator = Validation::createValidator();
        $collection = [
            'date' => [
                new Assert\Date([
                    'message' => self::MESSAGE_DATE
                ])
            ]
        ];
        if (\array_key_exists('count', $input)) {
            $collection['count'] = [
                new Assert\LessThanOrEqual([
                    'message' => self::MESSAGE_MAX_VALUE,
                    'value' => $this->getParameter('items.max.result')
                ])
            ];
        }
        $constraint = new Assert\Collection($collection);
        $violations = $validator->validate($input, $constraint);

        return $violations;
    }

    /**
     * @Route("/count/{inputDate}", methods={"GET", "OPTIONS"},
     *     name="item_count",
     *     requirements={"inputDate"="\d{4}-\d{2}-\d{2}"})
     * @param string $inputDate
     * @param ItemRepository $itemRepository
     * @return ApiResponse
     * @throws ClassException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function count(string $inputDate, ItemRepository $itemRepository)
    {
        $user = $this->getUser();
        if (!($user instanceof User)) {
            throw new ClassException($user, '$user', User::class);
        }

        $errors = $this->validateGet([
            'date' => $inputDate,
        ]);
        if (\count($errors) > 0) {
            throw new ValidationException($errors);
        }
        $date = new \DateTime(
            $inputDate,
            new DateTimeZone('+00:00')
        );
        $count = $itemRepository->getCount($user, $date);

        return new ApiResponse($count);
    }

    /**
     * @Route("/create", methods={"POST", "OPTIONS"}, name="item_create")
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
        $errors = $this->validatePost($inputData);
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

        return new ApiResponse([
            'item' => $item->toArray(),
            'count' => $itemRepository->getCount($user, $item->getDate())
        ]);
    }

    /**
     * @Route("/read/{inputDate}/{page}/{count}", methods={"GET", "OPTIONS"},
     *     name="item_read_items",
     *     requirements={"inputDate"="\d{4}-\d{2}-\d{2}", "count"="\d+", "page"="\d+"})
     * @param string $inputDate
     * @param int $page
     * @param int $count
     * @param ItemRepository $itemRepository
     * @return JsonResponse
     * @throws ClassException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Exception
     */
    public function readItems(string $inputDate, int $page, int $count,
                              ItemRepository $itemRepository)
    {

        $errors = $this->validateGet([
            'date' => $inputDate,
            'count' => $count
        ]);
        if (\count($errors) > 0) {
            throw new ValidationException($errors);
        }
        $date = new \DateTime(
            $inputDate,
            new DateTimeZone('+00:00')
        );
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
        return new ApiResponse([
            'items' => $result,
            'count' => $itemRepository->getCount($user, $date)
        ]);

    }

    public function update()
    {

    }

    /**
     * @Route("/delete/{uuid}", methods={"POST", "OPTIONS"}, name="item_delete",
     *     requirements={"uuid" = "[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}"})
     * @param string $uuid
     * @param ItemRepository $itemRepository
     * @param UserInterface $user
     * @return JsonResponse
     * @throws ClassException
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

        return new ApiResponse();
    }
}
