<?php

namespace App\Controller;

use App\Entity\Livre;
use App\Form\LivreType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\LivreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LivreController extends AbstractController
{
    #[Route('/livres', name: 'app_livre_index')]
    public function index(LivreRepository $livreRepository): Response
    {
        $livres = $livreRepository->findAll();

        return $this->render('livre/index.html.twig', [
            'livres' => $livres,
        ]);
    }
    #[Route('/livre/nouveau', name: 'app_livre_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($livre);
            $entityManager->flush();

            return $this->redirectToRoute('app_livre_index');
        }

        return $this->render('livre/new.html.twig', [
            'form' => $form,
        ]);
    }
}