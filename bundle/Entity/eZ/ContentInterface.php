<?php



declare(strict_types=1);

namespace CodeRhapsodie\IbexaMailingBundle\Entity\eZ;

use Ibexa\Contracts\Core\Repository\Values\Content\Content as eZContent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location as eZLocation;

interface ContentInterface
{
    public function getLocationId(): ?int;

    public function setLocationId(int $locationId): ContentInterface;

    public function getContent(): ?eZContent;

    public function setContent(eZContent $content): ContentInterface;

    public function getLocation(): ?eZLocation;

    public function setLocation(eZLocation $location): ContentInterface;
}
