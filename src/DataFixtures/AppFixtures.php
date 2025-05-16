<?php
namespace App\DataFixtures;

use App\Entity\Tweet;
use App\Entity\Like;
use App\Entity\Subscription;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use DateTime;
use Faker\Factory;
use App\Entity\Notification;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Create Users
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setApiKey(bin2hex(random_bytes(32)));
            $user->setPseudo("user$i");
            $user->setPassword(password_hash("password$i", PASSWORD_BCRYPT));
            $user->setRegistrationDate(new DateTime());
            $manager->persist($user);
            $users[] = $user;
        }

        // Create Tweets
        $tweets = [];
        foreach ($users as $user) {
            $tweetCount = rand(1, 4);
            for ($i = 0; $i < $tweetCount; $i++) {
                $tweet = new Tweet();
                $tweet->setContent($faker->realText());
                $tweet->setPublicationDate($faker->dateTimeBetween('-1 year', 'now'));
                $tweet->setTweeter($user);
                $manager->persist($tweet);
                $tweets[] = $tweet;
            }
        }

        $manager->flush();
        
        // Create Likes
        foreach ($tweets as $tweet) {
            $liker = $users[array_rand($users)];
            $like = new Like();
            $like->setLikedTweet($tweet);
            $like->setTweeter($liker);
            $like->setLikeDate(new DateTime());

            $manager->persist($like);

            if ($liker !== $tweet->getTweeter()) {
                $notif = new Notification();
                $notif->setTarget($tweet->getTweeter());
                $notif->setType('like');
                $notif->setPayload([
                    'tweetId' => $tweet->getId(),
                    'liker' => $liker->getPseudo(),
                    'likerId' => $liker->getId(),
                ]);
                $notif->setIsRead(false);
                $notif->setCreatedAt(new DateTimeImmutable());
                $manager->persist($notif);
            }
        }

        // Create Subscriptions
        foreach ($users as $follower) {
            $followed = $users[array_rand($users)];
            if ($follower !== $followed) {
                $sub = new Subscription();
                $sub->setFollowingUser($follower);
                $sub->setFollowedUser($followed);
                $sub->setSubscriptionDate(new DateTime());
                $manager->persist($sub);

                $notif = new Notification();
                $notif->setTarget($followed);
                $notif->setType('follow');
                $notif->setPayload([
                    'follower' => $follower->getPseudo(),
                    'followerId' => $follower->getId(),
                ]);
                $notif->setIsRead(false);
                $notif->setCreatedAt(new DateTimeImmutable());
                $manager->persist($notif);
            }
        }

        $manager->flush();
    }
}
