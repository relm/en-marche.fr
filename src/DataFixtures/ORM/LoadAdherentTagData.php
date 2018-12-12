<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\AdherentTag;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadAdherentTagData extends AbstractFixture implements FixtureInterface
{
    const ADHERENT_TAG = [
        'AT001' => 'Élu',
        'AT002' => 'Très actif',
        'AT003' => 'Actif',
        'AT004' => 'Peu actif',
        'AT005' => 'Médiation',
        'AT006' => 'Suppléant',
        'AT007' => 'Idées',
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::ADHERENT_TAG as $code => $name) {
            $adherentTag = new AdherentTag($name);
            $manager->persist($adherentTag);
            $this->addReference('adherent_tag_'.strtolower($code), $adherentTag);
        }

        $manager->flush();
    }
}
