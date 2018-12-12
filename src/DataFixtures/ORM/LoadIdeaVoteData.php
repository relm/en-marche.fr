<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\IdeasWorkshop\Vote;
use AppBundle\Entity\IdeasWorkshop\VoteTypeEnum;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadIdeaVoteData extends AbstractFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $adherents = [
            $this->getReference('adherent-2'),
            $this->getReference('adherent-3'),
            $this->getReference('adherent-4'),
            $this->getReference('adherent-5'),
            $this->getReference('adherent-6'),
            $this->getReference('adherent-7'),
            $this->getReference('adherent-9'),
        ];

        $ideas = [
            $this->getReference('idea-help-ecology'),
            $this->getReference('idea-peace'),
            $this->getReference('idea-help-people'),
        ];

        foreach ($adherents as $key => $adherent) {
            foreach (VoteTypeEnum::toArray() as $type) {
                foreach ($ideas as $idea) {
                    if (
                        !(\in_array($key, [4, 5, 7]) && VoteTypeEnum::INNOVATIVE === $type)
                        || !(\in_array($key, [2, 3, 5]) && VoteTypeEnum::FEASIBLE === $type)
                        || !(2 === $key && VoteTypeEnum::IMPORTANT === $type)
                    ) {
                        $vote = new Vote(
                            $idea,
                            $adherent,
                            $type
                        );

                        $manager->persist($vote);
                    }
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            LoadAdherentData::class,
            LoadIdeaData::class,
        ];
    }
}
