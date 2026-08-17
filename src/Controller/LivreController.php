<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Livre;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LivreController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_livre_index');
    }

    #[Route('/livres', name: 'app_livre_index')]
    public function index(LivreRepository $livreRepository): Response
    {
        $livres = $livreRepository->findAll();

        return $this->render('livre/index.html.twig', [
            'livres' => $livres,
        ]);
    }
    #[Route('/livre/{id}', name: 'app_livre_show', requirements: ['id' => '\d+'])]
    public function show(Livre $livre): Response
    {
        return $this->render('livre/show.html.twig', [
            'livre' => $livre,
        ]);
    }
    #[Route('/livre/{id}/modifier', name: 'app_livre_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_VENDEUR')]
    public function edit(Livre $livre, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($livre->getVendeur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres livres.');
        }

        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_livre_show', ['id' => $livre->getId()]);
        }

        return $this->render('livre/edit.html.twig', [
            'form' => $form,
            'livre' => $livre,
        ]);
    }

    #[Route('/livre/{id}/supprimer', name: 'app_livre_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_VENDEUR')]
    public function delete(Livre $livre, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($livre->getVendeur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres livres.');
        }

        if ($this->isCsrfTokenValid('delete' . $livre->getId(), $request->request->get('_token'))) {
            $entityManager->remove($livre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_livre_index');
    }
    #[Route('/livres/recherche', name: 'app_livre_search')]
    public function search(Request $request, LivreRepository $livreRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        $livres = $livreRepository->createQueryBuilder('l')
            ->where('l.titre LIKE :query')
            ->orWhere('l.auteur LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();

        $data = array_map(function ($livre) {
            return [
                'id' => $livre->getId(),
                'titre' => $livre->getTitre(),
                'auteur' => $livre->getAuteur(),
                'prix' => $livre->getPrix(),
                'stock' => $livre->getStock(),
            ];
        }, $livres);

        return new JsonResponse($data);
    }

    #[Route('/livre/nouveau', name: 'app_livre_new')]
    #[IsGranted('ROLE_VENDEUR')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $livre = new Livre();
        $livre->setVendeur($this->getUser());

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