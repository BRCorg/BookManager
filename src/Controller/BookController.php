<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/books', name: 'book_')]
class BookController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UploadService $uploader,
        SluggerInterface $slugger
    ): Response {
        $book = new Book();

        $form = $this->createForm(BookType::class, $book, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // propriétaire
            $book->setUser($this->getUser());

            // slug basé sur le titre
            $book->setSlug(strtolower($slugger->slug($book->getTitle())));

            // upload image (jpg/png) + nommage slug + timestamp
            /** @var UploadedFile|null $file */
            $file = $form->get('coverFile')->getData();
            if ($file) {
                $relativePath = $uploader->uploadCover($file, $book->getTitle());
                $book->setCoverImage($relativePath); // ex: uploads/covers/mon-titre-1696000000.jpg
            }

            $em->persist($book);
            $em->flush();

            $this->addFlash('success', 'Livre ajouté avec succès.');

            // redirige vers la liste (ou la page détail si tu ajoutes la route show)
            return $this->redirectToRoute('book_index');
        }

        // tu peux garder templates/book/new.html.twig ou utiliser form.html.twig si tu préfères
        return $this->render('book/new.html.twig', [
            'form' => $form,
        ]);
    }

    // (optionnel mais recommandé par l’exo : détail par slug)
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', ['book' => $book]);
    }
}
