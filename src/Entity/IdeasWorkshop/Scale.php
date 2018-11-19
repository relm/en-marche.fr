<?php

namespace AppBundle\Entity\IdeasWorkshop;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use AppBundle\Entity\EntityIdentityTrait;
use AppBundle\Entity\EntityNameSlugTrait;
use AppBundle\Entity\EntityPublishableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ScaleRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="scale_slug_unique", columns="slug")
 *     }
 * )
 *
 * @UniqueEntity("name")
 *
 * @Algolia\Index(autoIndex=false)
 */
class Scale
{
    use EntityIdentityTrait;
    use EntityNameSlugTrait;
    use EntityPublishableTrait;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Note", mappedBy="scale")
     */
    private $notes;

    public function __construct()
    {
        $this->notes = new ArrayCollection();
    }

    public function addNote(Note $note): void
    {
        $this->notes->add($note);
    }

    public function removeNote(Note $note): void
    {
        $this->notes->removeElement($note);
    }

    public function getNotes(): ArrayCollection
    {
        return $this->notes;
    }
}
