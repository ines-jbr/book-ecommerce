<?php

namespace App\Controller;

use App\Service\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PanierController extends AbstractController
{
    #[Route('/panier', name: 'app_panier_index')]
    #[IsGranted('ROLE_USER')]
    public function index(Panier $panier): Response
    {
        return $this->render('panier/index.html.twig', [
            'lignes' => $panier->getLignes(),
            'total' => $panier->getTotal(),
        ]);
    }

    #[Route('/panier/modifier/{id}', name: 'app_panier_modifier', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function modifier(int $id, Request $request, Panier $panier): Response
    {
        $quantite = (int) $request->request->get('quantite', 1);
        $panier->modifierQuantite($id, $quantite);

        return $this->redirectToRoute('app_panier_index');
    }

    #[Route('/panier/retirer/{id}', name: 'app_panier_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function retirer(int $id, Panier $panier): Response
    {
        $panier->retirer($id);

        return $this->redirectToRoute('app_panier_index');
    }
}