# La Perle d'Or — identité visuelle

`logo-source.pdf` est le fichier vectoriel fourni par la marque (9 planches,
1080 × 1080 pt, Adobe Illustrator, aucun texte : les lettrages sont
vectorisés). Il n'est pas servi par le site : les fichiers réellement utilisés
sont dans `frontend/public/brand/`.

## Couleurs officielles

Prélevées directement dans les opérateurs de couleur du PDF (aucune
approximation) :

| Rôle  | Hex       | PDF (rg)              |
| ----- | --------- | --------------------- |
| Bleu  | `#000046` | `0 0 0.275 rg`        |
| Or    | `#FFBD00` | `1 0.741 0 rg`        |
| Blanc | `#FFFFFF` | `1 1 1 rg`            |

`1 × 0.741 × 255 = 188,955 → 189 = 0xBD` : l'or est bien `#FFBD00` (le rendu
raster de MuPDF affiche 188 par troncature, sans incidence).

## Planches du PDF

| Page | Contenu                                                          |
| ---- | ---------------------------------------------------------------- |
| 1    | logo vertical (emblème au-dessus du lettrage), fond bleu, or + blanc |
| 2    | idem, fond blanc, or + noir                                      |
| 3    | idem, fond noir, or + blanc                                      |
| 4    | version monochrome blanche                                       |
| 5    | lettrage seul                                                    |
| 6    | emblème seul                                                     |
| 7    | logo horizontal (emblème à gauche), fond bleu, or + blanc         |
| 8    | seconde composition horizontale                                  |
| 9    | logo vertical, fond blanc, or + bleu                             |

## Fichiers générés (`frontend/public/brand/`)

| Fichier                   | Source | Usage                                        |
| ------------------------- | ------ | -------------------------------------------- |
| `logo-horizontal-dark.svg`| p. 7   | barre de navigation, en-tête (fond bleu)     |
| `logo-horizontal-light.svg`| p. 7  | variante pour fond clair (lettrage bleu)     |
| `logo-lockup-dark.svg`    | p. 1   | bloc marque sur fond bleu (pied de page)     |
| `logo-lockup-light.svg`   | p. 9   | bloc marque sur fond clair (404, devis)      |
| `logo-mark.svg`           | p. 6   | emblème seul                                 |
| `logo-mono-white.svg`     | p. 4   | version monochrome blanche                   |
| `icon.svg`                | p. 6   | icône d'application (carré bleu + emblème)   |

Le composant `frontend/src/components/Logo.tsx` ne fait que choisir la bonne
variante et fixer une hauteur : l'emblème et le lettrage sont toujours rendus
ensemble, dans un seul fichier.
