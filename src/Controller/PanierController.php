<?php

namespace App\Controller;

use App\Service\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use Doctrine\ORM\EntityManagerInterface;

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
        #[Route('/panier/valider', name: 'app_panier_valider', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function valider(Panier $panier, EntityManagerInterface $entityManager): Response
    {
        $lignes = $panier->getLignes();

        if (empty($lignes)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_panier_index');
        }

        // Vérifier que le stock est toujours suffisant pour chaque article avant de valider
        foreach ($lignes as $ligne) {
            if ($ligne['quantite'] > $ligne['livre']->getStock()) {
                $this->addFlash('error', 'Stock insuffisant pour "' . $ligne['livre']->getTitre() . '".');
                return $this->redirectToRoute('app_panier_index');
            }
        }

        $commande = new Commande();
        $commande->setClient($this->getUser());
        $commande->setDateCommande(new \DateTime());
        $commande->setStatut('en attente');
        $commande->setTotal($panier->getTotal());

        foreach ($lignes as $ligne) {
            $ligneCommande = new LigneCommande();
            $ligneCommande->setLivre($ligne['livre']);
            $ligneCommande->setQuantite($ligne['quantite']);
            $ligneCommande->setPrixUnitaire($ligne['livre']->getPrix());

            $commande->addLigne($ligneCommande);

            // Décrémenter le stock du livre
            $livre = $ligne['livre'];
            $livre->setStock($livre->getStock() - $ligne['quantite']);
        }

        $entityManager->persist($commande);
        $entityManager->flush();

        $panier->vider();

        $this->addFlash('success', 'Votre commande a été validée avec succès !');

        return $this->redirectToRoute('app_commande_show', ['id' => $commande->getId()]);
    }
        #[Route('/commande/{id}', name: 'app_commande_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function commandeShow(Commande $commande): Response
    {
        if ($commande->getClient() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        return $this->render('panier/commande_show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/panier/retirer/{id}', name: 'app_panier_retirer', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function retirer(int $id, Panier $panier): Response
    {
        $panier->retirer($id);

        return $this->redirectToRoute('app_panier_index');
    }
}