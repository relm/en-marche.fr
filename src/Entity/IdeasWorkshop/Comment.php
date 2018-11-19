<?php

namespace AppBundle\Entity\IdeasWorkshop;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\EntityIdentityTrait;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CommentRepository")
 *
 * @Algolia\Index(autoIndex=false)
 */
class Comment
{
    use EntityIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Thread", inversedBy="comments")
     */
    private $thread;

    /**
     * @ORM\Column(type="text")
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Adherent", inversedBy="notes")
     */
    private $adherent;

    public function getThread(): Thread
    {
        return $this->thread;
    }

    public function setThread($thread): void
    {
        $this->thread = $thread;
    }

    public function getAdherent(): Adherent
    {
        return $this->adherent;
    }

    public function setAdherent($adherent): void
    {
        $this->adherent = $adherent;
    }
}
