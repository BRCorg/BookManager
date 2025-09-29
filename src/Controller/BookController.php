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
    #[IsGranted('ROLE_ADMIN')]
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
            $book->setUser($this->getUser());
            $book->setSlug(strtolower($slugger->slug($book->getTitle())));

            /** @var UploadedFile|null $file */
            $file = $form->get('coverFile')->getData();
            if ($file) {
                $relativePath = $uploader->uploadCover($file, $book->getTitle());
                $book->setCoverImage($relativePath);
            }

            $em->persist($book);
            $em->flush();

            $this->addFlash('success', 'Livre ajouté avec succès.');
            return $this->redirectToRoute('book_index');
        }

        return $this->render('book/new.html.twig', ['form' => $form]);
    }

    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', ['book' => $book]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Book $book,
        EntityManagerInterface $em,
        UploadService $uploader,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(BookType::class, $book, ['is_edit' => true]);
        $form->handleRequest($request);
        $old = $book->getCoverImage();

        if ($form->isSubmitted() && $form->isValid()) {
            $book->setSlug(strtolower($slugger->slug($book->getTitle())));

            /** @var UploadedFile|null $file */
            $file = $form->get('coverFile')->getData();
            if ($file) {
                $uploader->delete($old);
                $book->setCoverImage($uploader->uploadCover($file, $book->getTitle()));
            }

            $em->flush();
            $this->addFlash('success', 'Livre mis à jour.');
            return $this->redirectToRoute('book_show', ['slug' => $book->getSlug()]);
        }

        return $this->render('book/new.html.twig', ['form' => $form]); // réutilise le même form
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Book $book, EntityManagerInterface $em, UploadService $uploader): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            $uploader->delete($book->getCoverImage());
            $em->remove($book);
            $em->flush();
            $this->addFlash('success', 'Livre supprimé.');
        }
        return $this->redirectToRoute('book_index');
    }
}
