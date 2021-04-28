<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CookieConsentRepository")
 * @ORM\Table(name="cookie_consent")
 */
class CookieConsent
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $ip;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $userAgent;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $frontofficeVersion;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $cookiePolicyVersion;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function setDate(): self
    {
        $utc = new \DateTimeZone('UTC');
        $date = new \DateTime("now");
        $date->setTimezone($utc);
        $date = $date->format('Y-m-d H:i:s');

        $this->date = $date;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function setFrontofficeVersion(string $frontofficeVersion): self
    {
        $this->frontofficeVersion = $frontofficeVersion;
        return $this;
    }

    public function setCookiePolicyVersion(string $cookiePolicyVersion): self
    {
        $this->cookiePolicyVersion = $cookiePolicyVersion;
        return $this;
    }
}