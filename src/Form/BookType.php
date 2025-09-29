<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $b->add('title', TextType::class, [
            'label'=>'Titre',
            'constraints'=>[new Assert\NotBlank(), new Assert\Length(min:2,max:255)]
        ])
            ->add('author', TextType::class, [
                'label'=>'Auteur', 'constraints'=>[new Assert\NotBlank()]
            ])
            ->add('description', TextareaType::class, [
                'label'=>'Description','required'=>false,'attr'=>['rows'=>6]
            ])
            ->add('genre', ChoiceType::class, [
                'label'=>'Genre',
                'choices'=>array_combine(Book::GENRES, Book::GENRES),
                'placeholder'=>'— Choisir —'
            ])
            ->add('coverFile', FileType::class, [
                'label'=>'Couverture (jpg/png)',
                'mapped'=>false,
                'required'=>!$isEdit,
                'constraints'=>array_filter([
                    $isEdit ? null : new Assert\NotBlank(),
                    new Assert\File([
                        'maxSize'=>'4M',
                        'mimeTypes'=>['image/jpeg','image/png'],
                        'mimeTypesMessage'=>'Formats autorisés : JPG/PNG',
                    ])
                ])
            ]);
    }

    public function configureOptions(OptionsResolver $r): void
    {
        $r->setDefaults(['data_class'=>Book::class,'is_edit'=>false]);
    }
}
