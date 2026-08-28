<?php

namespace App\Service;

use App\Entity\Livre;
use App\Repository\LivreRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class Panier
{
    private const SESSION_KEY = 'panier';

    public function __construct(
        private RequestStack $requestStack,
        private LivreRepository $livreRepository,
    ) {
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    /**
     * Retourne le panier brut : [livreId => quantite]
     */
    public function getPanierBrut(): array
    {
        return $this->getSession()->get(self::SESSION_KEY, []);
    }

    public function ajouter(int $livreId, int $quantite = 1): void
    {
        $panier = $this->getPanierBrut();

        if (isset($panier[$livreId])) {
            $panier[$livreId] += $quantite;
        } else {
            $panier[$livreId] = $quantite;
        }

        $this->getSession()->set(self::SESSION_KEY, $panier);
    }

    public function modifierQuantite(int $livreId, int $quantite): void
    {
        $panier = $this->getPanierBrut();

        if ($quantite <= 0) {
            unset($panier[$livreId]);
        } else {
            $panier[$livreId] = $quantite;
        }

        $this->getSession()->set(self::SESSION_KEY, $panier);
    }

    public function retirer(int $livreId): void
    {
        $panier = $this->getPanierBrut();
        unset($panier[$livreId]);
        $this->getSession()->set(self::SESSION_KEY, $panier);
    }

    public function vider(): void
    {
        $this->getSession()->remove(self::SESSION_KEY);
    }

    /**
     * Retourne les lignes du panier avec les objets Livre chargés et les sous-totaux.
     * Format : [['livre' => Livre, 'quantite' => int, 'sousTotal' => float], ...]
     */
    public function getLignes(): array
    {
        $panier = $this->getPanierBrut();
        $lignes = [];

        foreach ($panier as $livreId => $quantite) {
            $livre = $this->livreRepository->find($livreId);

            if ($livre) {
                $lignes[] = [
                    'livre' => $livre,
                    'quantite' => $quantite,
                    'sousTotal' => $livre->getPrix() * $quantite,
                ];
            }
        }

        return $lignes;
    }

    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getLignes() as $ligne) {
            $total += $ligne['sousTotal'];
        }

        return $total;
    }

    public function getNombreArticles(): int
    {
        return array_sum($this->getPanierBrut());
    }
}