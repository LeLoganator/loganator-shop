LOGANATOR SHOP V32 ULTIMATE — livrable initial

Base utilisée : archive V29.6-PERFORMANCE-AUTO disponible dans les fichiers, dont le dossier interne est nommé V29.5-PERFORMANCE-MAX. Aucun ZIP V31 n’était disponible dans les fichiers accessibles au moment de la construction.

Ajouts V32 réalisés dans cette livraison :
- Onglet Outils V32 dans le centre existant.
- Réglage de couleur d’accent et couleur de fond avec application visuelle locale.
- Choix de décor : cercles existants, grille discrète, ou aucun décor atmosphérique.
- Réglage de transparence du verre.
- Mode léger local réduisant les animations CSS.
- Export/import JSON des réglages visuels avec validation basique.
- Contrôle local simple de la page, du stockage et des outils V32.
- Conservation des fichiers produit et images présents dans l’archive source.

Limites importantes :
- Les réglages de cet onglet sont enregistrés dans le navigateur courant ; ils ne se synchronisent pas automatiquement entre appareils.
- Ce contrôle local n’est pas un test complet de toutes les fonctions Supabase.
- Les scripts intégrés ont passé `node --check`. Cela ne remplace pas un test réel du site dans un navigateur connecté à Supabase.
- Cette version ne prétend pas implémenter 200 nouveautés ni un million de fonctions : c’est une première V32 incrémentale construite à partir de l’archive réellement disponible.

Déploiement : remplacer les fichiers du dépôt par le contenu de ce dossier uniquement après avoir conservé une sauvegarde de la version publiée. Ne pas exécuter de SQL ni modifier Supabase pour les outils visuels V32.
