<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Tweet;
use App\Entity\Like;
use App\Entity\Subscription;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use DateTime;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
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
            for ($i = 1; $i <= 2; $i++) {
                $tweet = new Tweet();
                $tweet->setContent("Tweet $i by " . $user->getPseudo());
                $tweet->setPublicationDate(new DateTime());
                $tweet->setTweeter($user);
                $manager->persist($tweet);
                $tweets[] = $tweet;
            }
        }

        // Create Likes
        foreach ($tweets as $tweet) {
            $liker = $users[array_rand($users)];
            $like = new Like();
            $like->setLikedTweet($tweet);
            $like->setTweeter($liker);
            $like->setLikeDate(new DateTime());
            $manager->persist($like);
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
            }
        }

        $manager->flush();
    }
}
