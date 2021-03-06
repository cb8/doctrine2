<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * @group DDC-1228
 * @group DDC-1226
 */
class DDC1228Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1228User::class),
                $this->em->getClassMetadata(DDC1228Profile::class),
                ]
            );
        } catch (\Exception $e) {
        }
    }

    public function testOneToOnePersist()
    {
        $user = new DDC1228User;
        $profile = new DDC1228Profile();
        $profile->name = "Foo";
        $user->profile = $profile;

        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();
        $this->em->clear();

        /* @var $fetchedUser DDC1228User */
        $fetchedUser = $this->em->find(DDC1228User::class, $user->id);

        /* @var $fetchedProfile DDC1228Profile|GhostObjectInterface */
        $fetchedProfile = $fetchedUser->getProfile();

        self::assertInstanceOf(GhostObjectInterface::class, $fetchedProfile);
        self::assertInstanceOf(DDC1228Profile::class, $fetchedProfile);
        self::assertFalse($fetchedProfile->isProxyInitialized());

        $fetchedProfile->setName("Bar");

        self::assertTrue($fetchedProfile->isProxyInitialized());

        self::assertEquals("Bar", $fetchedProfile->getName());
        self::assertEquals(["id" => 1, "name" => "Foo"], $this->em->getUnitOfWork()->getOriginalEntityData($fetchedProfile));

        $this->em->flush();
        $this->em->clear();

        /* @var $otherFetchedUser DDC1228User */
        $otherFetchedUser = $this->em->find(DDC1228User::class, $fetchedUser->id);

        self::assertEquals("Bar", $otherFetchedUser->getProfile()->getName());
    }

    public function testRefresh()
    {
        $user = new DDC1228User;
        $profile = new DDC1228Profile();
        $profile->name = "Foo";
        $user->profile = $profile;

        $this->em->persist($user);
        $this->em->persist($profile);
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->getReference(DDC1228User::class, $user->id);

        $this->em->refresh($user);
        $user->name = "Baz";
        $this->em->flush();
        $this->em->clear();

        $user = $this->em->find(DDC1228User::class, $user->id);
        self::assertEquals("Baz", $user->name);
    }
}

/**
 * @ORM\Entity
 */
class DDC1228User
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    public $name = 'Bar';

    /**
     * @ORM\OneToOne(targetEntity=DDC1228Profile::class)
     * @var Profile
     */
    public $profile;

    public function getProfile()
    {
        return $this->profile;
    }
}

/**
 * @ORM\Entity
 */
class DDC1228Profile
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    public $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
