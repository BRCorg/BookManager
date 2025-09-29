<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Security\BookVoter;
use App\Service\UploadService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;


#[Route('/books', name: 'book_')]
class BookController extends AbstractController
{
    // PAGE PUBLIQUE + recherche + filtre
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $req, BookRepository $repo): Response
    {
        $q     = trim((string)$req->query->get('q'));
        $genre = $req->query->get('genre');

        return $this->render('book/index.html.twig', [
            'books'  => $repo->search($q ?: null, $genre ?: null, null),
            'q'      => $q,
            'genre'  => $genre,
            'genres' => Book::GENRES,
            'mine'   => false,
        ]);
    }

    // PAGE "Mes livres" (distincte)
    #[Route('/my', name: 'my', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function my(Request $req, BookRepository $repo): Response
    {
        $q     = trim((string) $req->query->get('q'));
        $genre = $req->query->get('genre');

        return $this->render('book/index.html.twig', [
            'books'  => $repo->search($q ?: null, $genre ?: null, $this->getUser()), // <-- ICI
            'q'      => $q,
            'genre'  => $genre,
            'genres' => Book::GENRES,
            'mine'   => true,
        ]);
    }


    // CRÉATION : tout utilisateur connecté peut créer
    #[Route('/new', name: 'new', methods: ['GET','POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $req, EntityManagerInterface $em, UploadService $uploader, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book, ['is_edit' => false])->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $book->setUser($this->getUser());
            $book->setSlug(strtolower($slugger->slug($book->getTitle())));

            /** @var UploadedFile|null $file */
            $file = $form->get('coverFile')->getData();
            if ($file) $book->setCoverImage($uploader->uploadCover($file, $book->getTitle()));

            $em->persist($book);
            $em->flush();

            $this->addFlash('success','Livre créé.');
            return $this->redirectToRoute('book_show', ['slug'=>$book->getSlug()]);
        }

        return $this->render('book/form.html.twig', ['form'=>$form, 'title'=>'Nouveau livre']);
    }

    // DÉTAIL via slug
    // DÉTAIL via slug
    #[Route('/{slug}', name: 'show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Book $book
    ): Response {
        return $this->render('book/show.html.twig', ['book' => $book]);
    }


    // ÉDITION : autorisée au créateur OU admin (via Voter)
    #[Route('/{id}/edit', name: 'edit', methods: ['GET','POST'])]
    public function edit(Request $req, Book $book, EntityManagerInterface $em, UploadService $uploader, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted(BookVoter::EDIT, $book);

        $form = $this->createForm(BookType::class, $book, ['is_edit'=>true])->handleRequest($req);
        $old = $book->getCoverImage();

        if ($form->isSubmitted() && $form->isValid()) {
            $book->setSlug(strtolower($slugger->slug($book->getTitle())));

            /** @var UploadedFile|null $file */
            $file = $form->get('coverFile')->getData();
            if ($file) {
                $uploader->delete($old); // supprime l’ancienne image
                $book->setCoverImage($uploader->uploadCover($file, $book->getTitle()));
            }

            $em->flush();
            $this->addFlash('success','Livre mis à jour.');
            return $this->redirectToRoute('book_show', ['slug'=>$book->getSlug()]);
        }

        return $this->render('book/form.html.twig', ['form'=>$form, 'title'=>'Modifier le livre']);
    }

    // SUPPRESSION : autorisée au créateur OU admin (via Voter)
    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $req, Book $book, EntityManagerInterface $em, UploadService $uploader): Response
    {
        $this->denyAccessUnlessGranted(BookVoter::DELETE, $book);

        if ($this->isCsrfTokenValid('delete'.$book->getId(), $req->request->get('_token'))) {
            $uploader->delete($book->getCoverImage());
            $em->remove($book);
            $em->flush();
            $this->addFlash('success','Livre supprimé.');
        }
        return $this->redirectToRoute('book_index');
    }
}
