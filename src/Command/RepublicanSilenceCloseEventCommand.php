<?php

namespace AppBundle\Command;

use AppBundle\Entity\RepublicanSilence;
use AppBundle\Event\EventCanceledHandler;
use AppBundle\Repository\CitizenActionRepository;
use AppBundle\Repository\EventRepository;
use AppBundle\RepublicanSilence\RepublicanSilenceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RepublicanSilenceCloseEventCommand extends Command
{
    protected static $defaultName = 'app:republican-silence:close-event';

    private $manager;
    private $eventRepository;
    private $actionRepository;
    private $eventCanceledHandler;

    public function __construct(
        RepublicanSilenceManager $manager,
        EventRepository $eventRepository,
        CitizenActionRepository $actionRepository,
        EventCanceledHandler $eventCanceledHandler
    ) {
        parent::__construct();

        $this->manager = $manager;
        $this->eventRepository = $eventRepository;
        $this->actionRepository = $actionRepository;
        $this->eventCanceledHandler = $eventCanceledHandler;
    }

    protected function configure()
    {
        $this
            ->setDescription('This command closes each committee event or citizen action when it matches a republican silence criteria')
            ->addArgument('interval', InputArgument::REQUIRED, 'Interval of time (in minutes) to search Event/Action')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        [$startDate, $endDate] = $this->getDates((int) $input->getArgument('interval'));

        foreach ($this->getSilences($startDate, $endDate) as $silence) {
            $this->closeEvents($silence);
            $this->closeActions($silence);
        }
    }

    private function getDates(int $interval): array
    {
        return [
            new \DateTime(),
            new \DateTime(sprintf('+%d minutes', $interval)),
        ];
    }

    /**
     * @return RepublicanSilence[]
     */
    private function getSilences(\DateTimeInterface $startDate, \DateTimeInterface $endDate): iterable
    {
        return $this->manager->getRepublicanSilencesBetweenDates($startDate, $endDate);
    }

    private function closeEvents(RepublicanSilence $silence): void
    {
        $events = $this->eventRepository->findStartedEventBetweenDatesForTags(
            (clone $silence->getBeginAt())->modify('-30 minutes'),
            $silence->getFinishAt(),
            $silence->getReferentTags()->toArray()
        );

        foreach ($events as $event) {
            $this->eventCanceledHandler->handle($event);
        }
    }

    private function closeActions(RepublicanSilence $silence): void
    {
        $actions = $this->actionRepository->findStartedEventBetweenDatesForTags(
            (clone $silence->getBeginAt())->modify('-30 minutes'),
            $silence->getFinishAt(),
            $silence->getReferentTags()->toArray()
        );

        foreach ($actions as $action) {
            $this->eventCanceledHandler->handle($action);
        }
    }
}
