<?php
declare(strict_types=1);
?>
<form class="contact-form" action="<?= e(site_url('api/contact.php')) ?>" method="post" novalidate data-contact-form>
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <div class="form-honeypot" aria-hidden="true">
    <label for="website">Ne pas renseigner ce champ</label>
    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
  </div>
  <div class="form-grid">
    <div class="field">
      <label for="name">Nom <span aria-hidden="true">*</span></label>
      <input id="name" name="name" type="text" autocomplete="name" minlength="2" maxlength="100" required>
      <p class="field-error" id="name-error"></p>
    </div>
    <div class="field">
      <label for="email">E-mail <span aria-hidden="true">*</span></label>
      <input id="email" name="email" type="email" autocomplete="email" maxlength="150" required>
      <p class="field-error" id="email-error"></p>
    </div>
    <div class="field">
      <label for="phone">Téléphone <span class="optional">optionnel</span></label>
      <input id="phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" maxlength="20">
      <p class="field-error" id="phone-error"></p>
    </div>
    <div class="field">
      <label for="project_type">Type de projet <span aria-hidden="true">*</span></label>
      <select id="project_type" name="project_type" required>
        <option value="">Choisir une option</option>
        <option value="Site vitrine">Site vitrine</option>
        <option value="Application web">Application web</option>
        <option value="E-commerce">E-commerce</option>
        <option value="Autre">Autre</option>
      </select>
      <p class="field-error" id="project_type-error"></p>
    </div>
    <div class="field">
      <label for="budget">Budget indicatif <span aria-hidden="true">*</span></label>
      <select id="budget" name="budget" required>
        <option value="">Choisir une tranche</option>
        <option value="Moins de 500€">Moins de 500 €</option>
        <option value="500€ - 1000€">500 € – 1 000 €</option>
        <option value="1000€ - 3000€">1 000 € – 3 000 €</option>
        <option value="3000€ - 5000€">3 000 € – 5 000 €</option>
        <option value="Plus de 5000€">Plus de 5 000 €</option>
        <option value="Non précisé">Je préfère en discuter</option>
      </select>
      <p class="field-error" id="budget-error"></p>
    </div>
    <div class="field">
      <label for="deadline">Délai souhaité <span aria-hidden="true">*</span></label>
      <select id="deadline" name="deadline" required>
        <option value="">Choisir un délai</option>
        <option value="Dès que possible">Dès que possible</option>
        <option value="Dans le mois">Dans le mois</option>
        <option value="Dans 1 à 3 mois">Dans 1 à 3 mois</option>
        <option value="Plus de 3 mois">Plus de 3 mois</option>
        <option value="À définir ensemble">À définir ensemble</option>
      </select>
      <p class="field-error" id="deadline-error"></p>
    </div>
    <div class="field field--wide">
      <label for="message">Parlez-moi de votre besoin <span aria-hidden="true">*</span></label>
      <textarea id="message" name="message" rows="6" minlength="30" maxlength="2000" required placeholder="Contexte, objectif, fonctionnalités importantes…"></textarea>
      <div class="field-meta"><p class="field-error" id="message-error"></p><span data-character-count>0 / 2 000</span></div>
    </div>
  </div>
  <label class="checkbox-field" for="consent">
    <input id="consent" name="consent" type="checkbox" value="1" required>
    <span>J’accepte que mes informations soient utilisées pour répondre à ma demande. <span aria-hidden="true">*</span></span>
  </label>
  <p class="field-error" id="consent-error"></p>
  <button class="button button--primary button--full" type="submit" data-submit-button>Envoyer ma demande <span aria-hidden="true">↗</span></button>
  <p class="form-status" role="status" aria-live="polite" data-form-status></p>
  <p class="form-note">Réponse sous 24–48 h ouvrées. Vos données ne sont jamais revendues.</p>
</form>
