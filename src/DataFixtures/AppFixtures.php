<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Faker\Factory;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        // $product = new Product();
        // $manager->persist($product);

        for ($i=0; $i < 30; $i++) { 
            $fake = Factory::create();

            $user = new User();
            $user->setEmail($fake->email);
            $user->setFirstname($fake->firstName());
            $user->setLastname($fake->lastName);
            $encoded = $this->passwordEncoder->encodePassword($user, $user->getLastname());
            $user->setPassword($encoded);
            $user->setRoles(["ROLE_USER"]);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
