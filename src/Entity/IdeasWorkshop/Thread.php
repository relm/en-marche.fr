<?php

namespace AppBundle\Entity\IdeasWorkshop;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use AppBundle\Entity\EntityIdentityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ThreadRepository")
 *
 * @Algolia\Index(autoIndex=false)
 */
class Thread
{
    use EntityIdentityTrait;

    /**
     * @ORM\ManyToOne(targetEntity="Note", inversedBy="threads")
     */
    private $note;

    /**
     * @ORM\OneToMany(targetEntity="Comment", mappedBy="thread")
     */
    private $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getNote(): Note
    {
        return $this->note;
    }

    public function setNote($note): void
    {
        $this->note = $note;
    }

    public function addComment(Comment $comment): void
    {
        $this->comments->add($comment);
    }

    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    public function getComments(): ArrayCollection
    {
        return $this->comments;
    }
}
