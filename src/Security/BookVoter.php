<?php

namespace App\Security;

use App\Entity\Book;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookVoter extends Voter
{
    public const EDIT='BOOK_EDIT'; public const DELETE='BOOK_DELETE';

    protected function supports(string $attr, $subject): bool
    {
        return in_array($attr,[self::EDIT,self::DELETE],true) && $subject instanceof Book;
    }

    protected function voteOnAttribute(string $attr, $book, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) return true;   // admin : tout accès
        return $book->getUser() === $user;                                   // propriétaire uniquement
    }
}
