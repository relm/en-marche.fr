<?php

namespace AppBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use AppBundle\IdeasWorkshop\Note;
use Symfony\Bridge\Doctrine\RegistryInterface;

class NoteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Note::class);
    }
}
