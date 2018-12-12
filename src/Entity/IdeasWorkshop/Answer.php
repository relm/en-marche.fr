<?php

namespace AppBundle\Entity\IdeasWorkshop;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="ideas_workshop_answer")
 * @ORM\Entity
 *
 * @Algolia\Index(autoIndex=false)
 */
class Answer
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @Groups("idea_read")
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity="Question")
     */
    private $question;

    /**
     * @Groups({"idea_read"})
     * @ORM\OneToMany(targetEntity="Thread", mappedBy="answer")
     */
    private $threads;

    /**
     * @ORM\ManyToOne(targetEntity="Idea", inversedBy="answers")
     */
    private $idea;

    public function __construct(
        string $content,
        Question $question
    ) {
        $this->content = $content;
        $this->question = $question;
        $this->threads = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getQuestion(): Question
    {
        return $this->question;
    }

    public function setQuestion(Question $question): void
    {
        $this->question = $question;
    }

    public function addThread(Thread $thread): void
    {
        if (!$this->threads->contains($thread)) {
            $this->threads->add($thread);
            $thread->setAnswer($this);
        }
    }

    public function removeThread(Thread $thread): void
    {
        $this->threads->removeElement($thread);
    }

    public function getThreads(): Collection
    {
        return $this->threads;
    }

    public function getIdea(): ?Idea
    {
        return $this->idea;
    }

    public function setIdea(Idea $idea): void
    {
        $this->idea = $idea;
    }
}
