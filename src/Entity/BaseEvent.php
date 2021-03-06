<?php

namespace App\Entity;

use App\Address\AddressInterface;
use App\Address\GeoCoder;
use App\Entity\Report\ReportableInterface;
use App\Geocoder\GeoPointInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation as SymfonySerializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="events",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="event_uuid_unique", columns="uuid"),
 *         @ORM\UniqueConstraint(name="event_slug_unique", columns="slug")
 *     },
 *     indexes={
 *         @ORM\Index(columns={"begin_at"}),
 *         @ORM\Index(columns={"finish_at"}),
 *         @ORM\Index(columns={"status"})
 *     }
 * )
 * @ORM\AssociationOverrides({
 *     @ORM\AssociationOverride(name="zones",
 *         joinTable=@ORM\JoinTable(
 *             name="event_zone"
 *         )
 *     )
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "event": "App\Entity\Event",
 *     "citizen_action": "App\Entity\CitizenAction",
 *     "institutional_event": "App\Entity\InstitutionalEvent",
 *     "municipal_event": "App\Entity\MunicipalEvent",
 * })
 */
abstract class BaseEvent implements GeoPointInterface, ReportableInterface, ReferentTaggableEntity, AddressHolderInterface, ZoneableEntity
{
    const EVENT_TYPE = 'event';
    const CITIZEN_ACTION_TYPE = 'citizen_action';
    const INSTITUTIONAL_EVENT_TYPE = 'institutional_event';

    const STATUS_SCHEDULED = 'SCHEDULED';
    const STATUS_CANCELLED = 'CANCELLED';

    const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_CANCELLED,
    ];

    const ACTIVE_STATUSES = [
        self::STATUS_SCHEDULED,
    ];

    use EntityIdentityTrait;
    use EntityCrudTrait;
    use EntityPostAddressTrait;
    use EntityReferentTagTrait;
    use EntityZoneTrait;
    use EntityTimestampableTrait;

    /**
     * @var Collection|ReferentTag[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\ReferentTag")
     * @ORM\JoinTable(
     *     name="event_referent_tag",
     *     joinColumns={
     *         @ORM\JoinColumn(name="event_id", referencedColumnName="id", onDelete="CASCADE")
     *     },
     *     inverseJoinColumns={
     *         @ORM\JoinColumn(name="referent_tag_id", referencedColumnName="id", onDelete="CASCADE")
     *     }
     * )
     */
    protected $referentTags;

    /**
     * @var string|null
     *
     * @ORM\Column(length=100)
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     *
     * @SymfonySerializer\Groups({"event_read"})
     *
     * @Assert\Length(max=100)
     */
    protected $name;

    /**
     * The event canonical name.
     *
     * @var string|null
     *
     * @ORM\Column(length=100)
     */
    protected $canonicalName;

    /**
     * @var string|null
     *
     * @ORM\Column(length=130)
     * @Gedmo\Slug(
     *     fields={"beginAt", "canonicalName"},
     *     dateFormat="Y-m-d",
     *     handlers={@Gedmo\SlugHandler(class="App\Event\UniqueEventNameHandler")}
     * )
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $slug;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(length=50)
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     * @JMS\SerializedName("timeZone")
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $timeZone;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime")
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     * @JMS\SerializedName("beginAt")
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $beginAt;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime")
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     * @JMS\SerializedName("finishAt")
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $finishAt;

    /**
     * @var Adherent|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Adherent")
     * @ORM\JoinColumn(onDelete="RESTRICT")
     */
    protected $organizer;

    /**
     * @var int
     *
     * @ORM\Column(type="smallint", options={"unsigned": true})
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     * @JMS\SerializedName("participantsCount")
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $participantsCount;

    /**
     * @var string|null
     *
     * @ORM\Column(length=20)
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected $published = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     *
     * @SymfonySerializer\Groups({"event_read"})
     */
    protected $capacity;

    /**
     * Mapping to be defined in child classes.
     *
     * @var BaseEventCategory|null
     */
    protected $category;

    public function __toString(): string
    {
        return $this->name ?: '';
    }

    protected static function canonicalize(string $name)
    {
        return mb_strtolower($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCanonicalName(): ?string
    {
        return $this->canonicalName;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getCategoryName(): string
    {
        return $this->category->getName();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBeginAt(): \DateTimeInterface
    {
        return $this->beginAt;
    }

    public function getLocalBeginAt(): \DateTimeInterface
    {
        return (clone $this->beginAt)->setTimezone(new \DateTimeZone($this->getTimeZone()));
    }

    public function getFinishAt(): \DateTimeInterface
    {
        return $this->finishAt;
    }

    public function getLocalFinishAt(): \DateTimeInterface
    {
        return (clone $this->finishAt)->setTimezone(new \DateTimeZone($this->getTimeZone()));
    }

    public function getOrganizer(): ?Adherent
    {
        return $this->organizer;
    }

    public function getOrganizerName(): ?string
    {
        return $this->organizer ? $this->organizer->getFirstName() : '';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getParticipantsCount(): int
    {
        return $this->participantsCount;
    }

    public function incrementParticipantsCount(int $increment = 1): void
    {
        $this->participantsCount += $increment;
    }

    public function decrementParticipantsCount(int $increment = 1): void
    {
        $this->participantsCount = max($this->participantsCount - $increment, 0);
    }

    public function setName(string $name): void
    {
        $this->name = ucfirst($name);
        $this->canonicalName = static::canonicalize($name);
    }

    public function getTimeZone(): string
    {
        return $this->timeZone;
    }

    public function setTimeZone(string $timeZone): void
    {
        $this->timeZone = $timeZone;
    }

    public function isFinished(): bool
    {
        $finishAt = new \DateTimeImmutable(
            $this->finishAt->format('Y-m-d H:i'),
            $timezone = new \DateTimeZone($this->timeZone ?? GeoCoder::DEFAULT_TIME_ZONE)
        );
        $now = new \DateTime('now');

        return $finishAt < $now->setTimezone($timezone);
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!\in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid status "%" given.', $status);
        }

        $this->status = $status;
    }

    public function publish(): void
    {
        $this->published = true;
    }

    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
    }

    public function isActive(): bool
    {
        return \in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isCancelled(): bool
    {
        return self::STATUS_CANCELLED === $this->status;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }

        return $this->participantsCount >= $this->capacity;
    }

    abstract public function getType();

    public function isCitizenAction(): bool
    {
        return self::CITIZEN_ACTION_TYPE === $this->getType();
    }

    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }

    /**
     * @JMS\VirtualProperty
     * @JMS\SerializedName("uuid"),
     * @JMS\Groups({"public", "event_read", "citizen_action_read"})
     */
    public function getUuidAsString(): string
    {
        return $this->getUuid()->toString();
    }

    public function getPostAddress(): ?AddressInterface
    {
        return $this->postAddress;
    }
}
