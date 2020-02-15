<link rel="stylesheet" href="ressources/css/horaires-previsionnels.css">

<div id="future">
  <h1>Découvrez votre futur emploi du temps !</h1>
  <h2>↯ Copiez vos horaires prévisionnels ci-dessous ↯</h2>
  <p>
    Accessible sur <a href="https://webapplis.utc.fr/smeappli/inscriptions/index.xhtml" target="_blank">cette page</a>, rentrez vos UVs puis cliquez sur le bouton <button id="horaires-p" onclick="this.title = 'Sur l\'autre site'" title="Pas ici banane">Horaires prévisionnels</button>, sélectionnez tous les horaires du tableau (<kbd id="c-a">Ctrl-A</kbd>, en vrac c'est pas grave) et copiez le ci-dessous.
  </p>

  <p>
    Si vous avez des UVs avec beaucoup de TD et TP, le nombre de combinaisons peut vite exploser ! C'est normal si l'affichage prend un peu de temps. La gestion des cours tous les 15 jours est assez sommaire également, si vous avez 3 créneaux en même temps ça risque de se superposer.
  </p>

  <textarea name="" id="text" rows="5" placeholder="→ Copiez vos horaires ici ←"></textarea>

  <p class="center" id="err"></p>
  <p id="stats"></p>
  <div id="render"></div>
</div>

<script src="ressources/js/horaires-previsionnels.js"></script>
