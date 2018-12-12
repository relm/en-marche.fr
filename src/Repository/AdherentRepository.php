<?php

namespace AppBundle\Repository;

use AppBundle\BoardMember\BoardMemberFilter;
use AppBundle\CitizenProject\CitizenProjectMessageNotifier;
use AppBundle\Collection\AdherentCollection;
use AppBundle\Coordinator\CoordinatorManagedAreaUtils;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\BoardMember\BoardMember;
use AppBundle\Entity\CitizenProject;
use AppBundle\Entity\Committee;
use AppBundle\Entity\CommitteeMembership;
use AppBundle\Entity\District;
use AppBundle\Entity\IdeasWorkshop\Idea;
use AppBundle\Entity\IdeasWorkshop\ThreadStatusEnum;
use AppBundle\Entity\ReferentManagedArea;
use AppBundle\Geocoder\Coordinates;
use AppBundle\Membership\CitizenProjectNotificationDistance;
use AppBundle\Statistics\StatisticsParametersFilter;
use AppBundle\Subscription\SubscriptionTypeEnum;
use AppBundle\Utils\RepositoryUtils;
use Cake\Chronos\Chronos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AdherentRepository extends ServiceEntityRepository implements UserLoaderInterface, UserProviderInterface, UserRepositoryInterface
{
    use NearbyTrait;
    use ReferentTrait;
    use UuidEntityRepositoryTrait {
        findOneByUuid as findOneByValidUuid;
    }

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Adherent::class);
    }

    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ) {
        return $this->findOneByEmail($username);
    }

    public function countElements(): int
    {
        return (int) $this
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countAdherents(): int
    {
        return (int) $this
            ->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->andWhere('a.adherent = 1')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Finds an Adherent instance by its email address.
     */
    public function findOneByEmail(string $email): ?Adherent
    {
        return $this->findOneBy(['emailAddress' => $email]);
    }

    public function isAdherent(string $email): bool
    {
        return (bool) $this
            ->createQueryBuilder('adherent')
            ->select('COUNT(adherent)')
            ->where('adherent.emailAddress = :email')
            ->andWhere('adherent.adherent = 1')
            ->setParameter('email', $email)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Finds an Adherent instance by its unique UUID.
     *
     * @return Adherent|null
     */
    public function findByUuid(string $uuid)
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    public function findByEmails(array $emails): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.emailAddress IN (:emails)')
            ->setParameter('emails', $emails)
            ->orderBy('a.firstName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function loadUserByUsername($username)
    {
        $query = $this
            ->createQueryBuilder('a')
            ->addSelect('pma')
            ->addSelect('ccpa')
            ->addSelect('cca')
            ->addSelect('cm')
            ->addSelect('c')
            ->addSelect('cpm')
            ->addSelect('cp')
            ->addSelect('bm')
            ->leftJoin('a.procurationManagedArea', 'pma')
            ->leftJoin('a.coordinatorCitizenProjectArea', 'ccpa')
            ->leftJoin('a.coordinatorCommitteeArea', 'cca')
            ->leftJoin('a.memberships', 'cm')
            ->leftJoin('cm.committee', 'c')
            ->leftJoin('a.citizenProjectMemberships', 'cpm')
            ->leftJoin('cpm.citizenProject', 'cp')
            ->leftJoin('a.boardMember', 'bm')
            ->where('a.emailAddress = :username')
            ->setParameter('username', $username)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    public function refreshUser(UserInterface $user)
    {
        $class = \get_class($user);
        $username = $user->getUsername();

        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(sprintf('User of type "%s" and identified by "%s" is not supported by this provider.', $class, $username));
        }

        if (!$user = $this->loadUserByUsername($username)) {
            throw new UsernameNotFoundException(sprintf('Unable to find Adherent user identified by "%s".', $username));
        }

        return $user;
    }

    public function supportsClass($class)
    {
        return Adherent::class === $class;
    }

    /**
     * Returns the total number of active Adherent accounts.
     */
    public function countActiveAdherents(): int
    {
        $query = $this
            ->createQueryBuilder('a')
            ->select('COUNT(a.uuid)')
            ->where('a.status = :status')
            ->setParameter('status', Adherent::ENABLED)
            ->getQuery()
        ;

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Finds the list of adherent matching the given list of UUIDs.
     */
    public function findList(array $uuids): AdherentCollection
    {
        if (!$uuids) {
            return new AdherentCollection();
        }

        $qb = $this->createQueryBuilder('a');

        $query = $qb
            ->where($qb->expr()->in('a.uuid', $uuids))
            ->getQuery()
        ;

        return new AdherentCollection($query->getResult());
    }

    /**
     * Finds the list of referents.
     *
     * @return Adherent[]
     */
    public function findReferents(): array
    {
        return $this
            ->createReferentQueryBuilder()
            ->getQuery()
            ->getResult()
        ;
    }

    public function findReferent(string $identifier): ?Adherent
    {
        $qb = $this->createReferentQueryBuilder();

        if (Uuid::isValid($identifier)) {
            $qb
                ->andWhere('a.uuid = :uuid')
                ->setParameter('uuid', Uuid::fromString($identifier)->toString())
            ;
        } else {
            $qb
                ->andWhere('LOWER(a.emailAddress) = :email')
                ->join('a.managedArea', 'managedArea')
                ->join('managedArea.tags', 'tags')
                ->setParameter('email', $identifier)
            ;
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function createReferentQueryBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('a')
            ->innerJoin('a.managedArea', 'managed_area')
            ->innerJoin('managed_area.tags', 'managed_area_tag')
            ->orderBy('LOWER(managed_area_tag.name)', 'ASC')
        ;
    }

    public function findReferentsByCommittee(Committee $committee): AdherentCollection
    {
        $qb = $this
            ->createReferentQueryBuilder()
            ->andWhere('managed_area_tag IN (:tags)')
            ->setParameter('tags', $committee->getReferentTags())
        ;

        return new AdherentCollection($qb->getQuery()->getResult());
    }

    public function findCoordinatorsByCitizenProject(CitizenProject $citizenProject): AdherentCollection
    {
        $qb = $this
            ->createQueryBuilder('a')
            ->innerJoin('a.coordinatorCitizenProjectArea', 'ccpa')
            ->where('ccpa.codes IS NOT NULL')
            ->andWhere('FIND_IN_SET(:code, ccpa.codes) > 0')
            ->andWhere('LENGTH(ccpa.codes) > 0')
            ->orderBy('LOWER(ccpa.codes)', 'ASC')
            ->setParameter('code', CoordinatorManagedAreaUtils::getCodeFromCitizenProject($citizenProject))
        ;

        return new AdherentCollection($qb->getQuery()->getResult());
    }

    /**
     * Finds the list of adherents managed by the given referent.
     *
     * @return Adherent[]
     */
    public function findAllManagedBy(Adherent $referent): array
    {
        if (!$referent->isReferent()) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->select('a', 'm')
            ->leftJoin('a.memberships', 'm')
            ->innerJoin('a.referentTags', 'tag')
            ->andWhere('tag IN (:tags)')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->orderBy('a.registeredAt', 'DESC')
            ->addOrderBy('a.firstName', 'ASC')
            ->addOrderBy('a.lastName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByNearCitizenProjectOrAcceptAllNotification(CitizenProject $citizenProject, int $offset = 0, bool $excludeSupervisor = true, int $radius = CitizenProjectMessageNotifier::RADIUS_NOTIFICATION_NEAR_PROJECT_CITIZEN): Paginator
    {
        $qb = $this->createNearbyQueryBuilder(
                new Coordinates(
                    $citizenProject->getLatitude(),
                    $citizenProject->getLongitude()
                )
            );

        $qb
            ->join('n.subscriptionTypes', 'subscriptionType')
            ->andWhere('subscriptionType.code = :citizen_project_subscription_type')
            ->setParameter('citizen_project_subscription_type', SubscriptionTypeEnum::CITIZEN_PROJECT_CREATION_EMAIL)
        ;

        $distance = $qb->expr()->orX();
        $distance->add($this->getNearbyExpression().' <= :distance_max')
            ->add('n.citizenProjectCreationEmailSubscriptionRadius = :citizenProjectCreationEmailSubscriptionRadius')
        ;

        $qb->andWhere($distance)
            ->setParameter('distance_max', $radius)
            ->setParameter('citizenProjectCreationEmailSubscriptionRadius', CitizenProjectNotificationDistance::DISTANCE_ALL)
        ;

        $having = $qb->expr()->orX();
        $having->add($this->getNearbyExpression().' <= n.citizenProjectCreationEmailSubscriptionRadius')
            ->add('n.citizenProjectCreationEmailSubscriptionRadius = :acceptAllNotification')
        ;
        $qb->having($having)
            ->setParameter('acceptAllNotification', CitizenProjectNotificationDistance::DISTANCE_ALL)
        ;

        if ($excludeSupervisor) {
            $qb->andWhere('n.uuid != :uuid')
                ->setParameter('uuid', $citizenProject->getCreatedBy())
            ;
        }

        $qb->setFirstResult($offset)
            ->setMaxResults(CitizenProjectMessageNotifier::NOTIFICATION_PER_PAGE)
        ;

        return new Paginator($qb);
    }

    public function searchBoardMembers(BoardMemberFilter $filter, Adherent $excludedMember): array
    {
        return $this
            ->createBoardMemberFilterQueryBuilder($filter, $excludedMember)
            ->getQuery()
            ->getResult()
        ;
    }

    public function paginateBoardMembers(BoardMemberFilter $filter, Adherent $excludedMember): Paginator
    {
        $qb = $this
            ->createBoardMemberFilterQueryBuilder($filter, $excludedMember)
            ->setFirstResult($filter->getOffset())
            ->setMaxResults(BoardMemberFilter::PER_PAGE)
        ;

        return new Paginator($qb);
    }

    private function createBoardMemberQueryBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('a')
            ->addSelect('bm')
            ->addSelect('ap')
            ->addSelect('ccpa')
            ->addSelect('cca')
            ->addSelect('cm')
            ->addSelect('bmr')
            ->innerJoin('a.boardMember', 'bm')
            ->leftJoin('a.procurationManagedArea', 'ap')
            ->leftJoin('a.coordinatorCitizenProjectArea', 'ccpa')
            ->leftJoin('a.coordinatorCommitteeArea', 'cca')
            ->leftJoin('a.memberships', 'cm')
            ->innerJoin('bm.roles', 'bmr')
        ;
    }

    private function createBoardMemberFilterQueryBuilder(BoardMemberFilter $filter, Adherent $excludedMember): QueryBuilder
    {
        $qb = $this->createBoardMemberQueryBuilder();

        $qb->andWhere('a != :member');
        $qb->setParameter('member', $excludedMember);

        if ($queryGender = $filter->getQueryGender()) {
            $qb->andWhere('a.gender = :gender');
            $qb->setParameter('gender', $queryGender);
        }

        if ($queryAgeMinimum = $filter->getQueryAgeMinimum()) {
            $dateMaximum = new \DateTime('now');
            $dateMaximum->modify('-'.$queryAgeMinimum.' years');

            $qb->andWhere('a.birthdate <= :dateMaximum');
            $qb->setParameter('dateMaximum', $dateMaximum->format('Y-m-d'));
        }

        if ($queryAgeMaximum = $filter->getQueryAgeMaximum()) {
            $dateMinimum = new \DateTime('now');
            $dateMinimum->modify('-'.$queryAgeMaximum.' years');

            $qb->andWhere('a.birthdate >= :dateMinimum');
            $qb->setParameter('dateMinimum', $dateMinimum->format('Y-m-d'));
        }

        if ($queryFirstName = $filter->getQueryFirstName()) {
            $qb->andWhere('a.firstName LIKE :firstName');
            $qb->setParameter('firstName', '%'.$queryFirstName.'%');
        }

        if ($queryLastName = $filter->getQueryLastName()) {
            $qb->andWhere('a.lastName LIKE :lastName');
            $qb->setParameter('lastName', '%'.$queryLastName.'%');
        }

        if ($queryPostalCode = $filter->getQueryPostalCode()) {
            $queryPostalCode = array_map('trim', explode(',', $queryPostalCode));

            $postalCodeExpression = $qb->expr()->orX();

            foreach ($queryPostalCode as $key => $postalCode) {
                $postalCodeExpression->add('a.postAddress.postalCode LIKE :postalCode_'.$key);
                $qb->setParameter('postalCode_'.$key, $postalCode.'%');
            }

            $qb->andWhere($postalCodeExpression);
        }

        if (\count($queryAreas = $filter->getQueryAreas())) {
            $areasExpression = $qb->expr()->orX();

            foreach ($queryAreas as $key => $area) {
                $areasExpression->add('bm.area = :area_'.$key);
                $qb->setParameter('area_'.$key, $area);
            }

            $qb->andWhere($areasExpression);
        }

        if (\count($queryRoles = $filter->getQueryRoles())) {
            $rolesExpression = $qb->expr()->orX();

            foreach ($queryRoles as $key => $role) {
                $rolesExpression->add('bmr.code = :board_member_role_'.$key);
                $qb->setParameter('board_member_role_'.$key, $role);
            }

            $qb->andWhere($rolesExpression);
        }

        return $qb;
    }

    public function findSavedBoardMember(BoardMember $owner): AdherentCollection
    {
        $qb = $this
            ->createBoardMemberQueryBuilder()
            ->where(':member MEMBER OF bm.owners')
            ->setParameter('member', $owner)
        ;

        return new AdherentCollection($qb->getQuery()->getResult());
    }

    /**
     * @return string[]
     */
    public function findAdherentsUuidByFirstName(string $firstName): array
    {
        $qb = $this->createQueryBuilder('a');

        $query = $qb
            ->select('a.uuid')
            ->where('LOWER(a.firstName) LIKE :firstName')
            ->setParameter('firstName', '%'.strtolower($firstName).'%')
            ->getQuery()
        ;

        return array_map(function (UuidInterface $uuid) {
            return $uuid->toString();
        }, array_column($query->getArrayResult(), 'uuid'));
    }

    /**
     * @return string[]
     */
    public function findAdherentsUuidByLastName(string $lastName): array
    {
        $qb = $this->createQueryBuilder('a');

        $query = $qb
            ->select('a.uuid')
            ->where('LOWER(a.lastName) LIKE :lastName')
            ->setParameter('lastName', '%'.strtolower($lastName).'%')
            ->getQuery()
        ;

        return array_map(function (UuidInterface $uuid) {
            return $uuid->toString();
        }, array_column($query->getArrayResult(), 'uuid'));
    }

    /**
     * @return string[]
     */
    public function findAdherentsUuidByEmailAddress(string $emailAddress): array
    {
        $qb = $this->createQueryBuilder('a');

        $query = $qb
            ->select('a.uuid')
            ->where('LOWER(a.emailAddress) LIKE :emailAddress')
            ->setParameter('emailAddress', '%'.strtolower($emailAddress).'%')
            ->getQuery()
        ;

        return array_map(function (UuidInterface $uuid) {
            return $uuid->toString();
        }, array_column($query->getArrayResult(), 'uuid'));
    }

    public function countByGender(): array
    {
        return $this->createQueryBuilder('a', 'a.gender')
            ->select('a.gender, COUNT(a) AS count')
            ->where('a.adherent = 1')
            ->andWhere('a.status = :status')
            ->setParameter('status', Adherent::ENABLED)
            ->groupBy('a.gender')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function countByGenderManagedBy(Adherent $referent): array
    {
        $this->checkReferent($referent);

        return $this->createQueryBuilder('a', 'a.gender')
            ->select('a.gender, COUNT(DISTINCT a) AS count')
            ->innerJoin('a.referentTags', 'tag')
            ->where('tag.id IN (:tags)')
            ->andWhere('a.adherent = 1')
            ->andWhere('a.status = :status')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->setParameter('status', Adherent::ENABLED)
            ->groupBy('a.gender')
            ->getQuery()
            ->getArrayResult()
        ;
    }

    public function countSupervisorsByGenderForReferent(Adherent $referent): array
    {
        $this->checkReferent($referent);

        $result = $this->createQueryBuilder('adherent', 'adherent.gender')
            ->select('adherent.gender, COUNT(DISTINCT adherent) AS count')
            ->join('adherent.memberships', 'membership')
            ->join('membership.committee', 'committee')
            ->join('committee.referentTags', 'tag')
            ->where('tag.id IN (:tags)')
            ->andWhere('committee.status = :status')
            ->andWhere('membership.privilege = :supervisor')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->setParameter('status', Committee::APPROVED)
            ->setParameter('supervisor', CommitteeMembership::COMMITTEE_SUPERVISOR)
            ->groupBy('adherent.gender')
            ->getQuery()
            ->getArrayResult()
        ;

        return $this->formatCount($result);
    }

    public function countMembersByGenderForReferent(Adherent $referent): array
    {
        $this->checkReferent($referent);

        $result = $this->createQueryBuilder('adherent', 'adherent.gender')
            ->select('adherent.gender, COUNT(DISTINCT adherent) AS count')
            ->join('adherent.memberships', 'membership')
            ->join('membership.committee', 'committee')
            ->innerJoin('committee.referentTags', 'tag')
            ->where('tag.id IN (:tags)')
            ->andWhere('committee.status = :status')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->setParameter('status', Committee::APPROVED)
            ->groupBy('adherent.gender')
            ->getQuery()
            ->getArrayResult()
        ;

        return $this->formatCount($result);
    }

    public function countCommitteeMembersInReferentManagedArea(Adherent $referent, StatisticsParametersFilter $filter = null, int $months = 5): array
    {
        $this->checkReferent($referent);

        $query = $this->createQueryBuilder('adherent', 'adherent.gender')
            ->select('COUNT(DISTINCT adherent.id) AS count, YEAR_MONTH(event.beginAt) as yearmonth')
            ->join('adherent.memberships', 'membership')
            ->join('membership.committee', 'committee')
            ->innerJoin('committee.referentTags', 'tag')
            ->where('tag IN (:tags)')
            ->andWhere('committee.status = :status')
            ->andWhere('membership.joinedAt >= :from')
            ->andWhere('membership.joinedAt <= :until')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->setParameter('status', Committee::APPROVED)
            ->setParameter('until', (new Chronos('now'))->setTime(23, 59, 59, 999))
            ->setParameter('from', (new Chronos("first day of -$months months"))->setTime(0, 0, 0, 000))
            ->groupBy('yearmonth')
        ;

        $query = RepositoryUtils::addStatstFilter($filter, $query)->getQuery();
        $query->useResultCache(true, 3600); // 1 hour

        return RepositoryUtils::aggregateCountByMonth($query->getArrayResult());
    }

    private function formatCount(array $count): array
    {
        array_walk($count, function (&$item) {
            $item = (int) $item['count'];
        });

        $count['total'] = array_sum($count);

        return $count;
    }

    public function countMembersManagedBy(Adherent $referent, \DateTimeInterface $until): int
    {
        $this->assertReferent($referent);

        $query = $this->createQueryBuilder('adherent')
            ->select('COUNT(DISTINCT adherent) AS count')
            ->innerJoin('adherent.referentTags', 'tag')
            ->where('tag IN (:tags)')
            ->andWhere('adherent.activatedAt <= :until')
            ->setParameter('tags', $referent->getManagedArea()->getTags())
            ->setParameter('until', $until)
            ->getQuery()
        ;

        // Let's cache past data as they are never going to change
        $firstDayOfMonth = (new Chronos('first day of this month'))->setTime(0, 0);
        if ($firstDayOfMonth > $until) {
            $query->useResultCache(true, 5184000); // 60 days
        }

        return (int) $query->getSingleScalarResult();
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function assertReferent(Adherent $referent): void
    {
        if (!$referent->isReferent()) {
            throw new \InvalidArgumentException('Adherent must be a referent.');
        }
    }

    /**
     * Finds enabled adherents in the deputy district that subscribed to deputy emails.
     */
    public function createQueryBuilderForDistrict(District $district): QueryBuilder
    {
        return $this->createQueryBuilder('adherent')
            ->distinct()
            ->join('adherent.referentTags', 'tag')
            ->join('adherent.subscriptionTypes', 'subscriptionType')
            ->where('tag = :tag')
            ->andWhere('adherent.status = :status')
            ->andWhere('subscriptionType.code = :citizen_project_subscription_type')
            ->setParameter('tag', $district->getReferentTag())
            ->setParameter('status', Adherent::ENABLED)
            ->setParameter('citizen_project_subscription_type', SubscriptionTypeEnum::DEPUTY_EMAIL)
        ;
    }

    /**
     * Count enabled adherents in the deputy district.
     */
    public function countAllInDistrict(District $district): int
    {
        return $this->createQueryBuilderForDistrict($district)
            ->select('COUNT(adherent)')
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function createDispatcherIteratorForDistrict(District $district, $offset = null): IterableResult
    {
        $qb = $this->createQueryBuilderForDistrict($district)
            ->select('partial adherent.{id, firstName, lastName, emailAddress}')
        ;

        if ($offset) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)->iterate();
    }

    public function countInManagedArea(ReferentManagedArea $managedArea): int
    {
        return $this->createQueryBuilder('adherent')
            ->select('COUNT(adherent)')
            ->innerJoin('adherent.referentTags', 'tag')
            ->where('tag IN (:tags)')
            ->andWhere('adherent.status = :status')
            ->setParameter('tags', $managedArea->getTags())
            ->setParameter('status', Adherent::ENABLED)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countSubscriberInManagedArea(ReferentManagedArea $managedArea): int
    {
        return $this->createQueryBuilder('adherent')
            ->select('COUNT(DISTINCT(adherent))')
            ->innerJoin('adherent.referentTags', 'tag')
            ->innerJoin('adherent.subscriptionTypes', 'subscriptiontypes')
            ->where('tag IN (:tags)')
            ->andWhere('adherent.status = :status')
            ->setParameter('tags', $managedArea->getTags())
            ->setParameter('status', Adherent::ENABLED)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countIdeaContributors(Idea $idea): int
    {
        return $this->createQueryBuilder('adherent')
            ->select('COUNT(adherent)')
            ->innerJoin('adherent.ideas', 'ideas')
            ->innerJoin('ideas.answers', 'answers')
            ->innerJoin('answers.threads', 'threads')
            ->innerJoin('threads.comments', 'comments')
            ->where('ideas IN(:idea)')
            ->setParameter('idea', $idea)
            ->andWhere('comments.deletedAt IS NULL')
            ->andWhere('threads.status = :status')
            ->setParameter('status', ThreadStatusEnum::APPROVED)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
