<?php

namespace AppBundle\Entity\IdeasWorkshop;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\EntityIdentityTrait;
use AppBundle\Entity\EntityNameSlugTrait;
use AppBundle\Entity\EntityTimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\NoteRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="note_slug_unique", columns="slug")
 *     }
 * )
 *
 * @UniqueEntity("name")
 *
 * @Algolia\Index(autoIndex=false)
 */
class Note
{
    use EntityIdentityTrait;
    use EntityTimestampableTrait;
    use EntityNameSlugTrait;

    private const PUBLISHED_INTERVAL = 'P3W';

    /**
     * @ORM\Column(length=100)
     */
    private $url;

    /**
     * @ORM\ManyToOne(targetEntity="Theme", inversedBy="notes")
     */
    private $theme;

    /**
     * @ORM\ManyToOne(targetEntity="Scale", inversedBy="notes")
     */
    private $scale;

    /**
     * @ORM\ManyToOne(targetEntity="Need", inversedBy="notes")
     */
    private $need;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Adherent", inversedBy="notes")
     */
    private $adherent;

    /**
     * @ORM\OneToMany(targetEntity="Thread", mappedBy="note")
     */
    private $threads;

    public function __construct()
    {
        $this->threads = new ArrayCollection();
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl($url): void
    {
        $this->url = $url;
    }

    public function getTheme(): ? Theme
    {
        return $this->theme;
    }

    public function setTheme($theme): void
    {
        $this->theme = $theme;
    }

    public function getScale(): ? Scale
    {
        return $this->scale;
    }

    public function setScale($scale): void
    {
        $this->scale = $scale;
    }

    public function getNeed(): ? Need
    {
        return $this->need;
    }

    public function setNeed($need): void
    {
        $this->need = $need;
    }

    public function getAdherent(): ? Adherent
    {
        return $this->adherent;
    }

    public function setAdherent($adherent): void
    {
        $this->adherent = $adherent;
    }

    public function addThread(Thread $thread): void
    {
        $this->threads->add($thread);
    }

    public function removeThread(Thread $thread): void
    {
        $this->threads->removeElement($thread);
    }

    public function getThreads(): ArrayCollection
    {
        return $this->threads;
    }

    public function getDaysBeforeDeadline(): int
    {
        $deadline = $this->createdAt->add(new \DateInterval(self::PUBLISHED_INTERVAL));
        $now = new \DateTime();

        return $deadline > $now ? 0 : $deadline->diff($now)->d;
    }
}
