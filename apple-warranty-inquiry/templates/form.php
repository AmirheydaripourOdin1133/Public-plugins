<?php
/**
 * Warranty inquiry form template.
 *
 * @package AppleWarranty
 * @var string $captcha_token
 * @var string $captcha_url
 * @var string $help_url
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="awi-wrap" id="awi-inquiry" dir="rtl">
	<div class="awi-notice awi-notice--error" id="awi-notice" role="alert" aria-live="polite" hidden>
		<p class="awi-notice__text" id="awi-notice-text"></p>
		<button type="button" class="awi-notice__close" id="awi-notice-close" aria-label="<?php esc_attr_e( 'بستن', 'apple-warranty-inquiry' ); ?>">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<form class="awi-form" id="awi-form" novalidate>
		<div class="awi-form__row">
			<div class="awi-field awi-field--captcha">
				<label class="awi-label" for="awi-captcha"><?php esc_html_e( 'کد امنیتی', 'apple-warranty-inquiry' ); ?></label>
				<div class="awi-captcha-field">
					<input
						type="text"
						class="awi-captcha-field__input"
						id="awi-captcha"
						name="captcha"
						inputmode="numeric"
						autocomplete="off"
						placeholder="<?php esc_attr_e( 'کد امنیتی نمایش داده شده را وارد کنید.', 'apple-warranty-inquiry' ); ?>"
					/>
					<button
						type="button"
						class="awi-captcha-field__media"
						id="awi-captcha-refresh"
						aria-label="<?php esc_attr_e( 'تازه‌سازی کد امنیتی', 'apple-warranty-inquiry' ); ?>"
					>
						<img src="<?php echo esc_url( $captcha_url ); ?>" alt="" class="awi-captcha-field__img" id="awi-captcha-img" width="110" height="44" />
					</button>
				</div>
				<input type="hidden" name="captcha_token" id="awi-captcha-token" value="<?php echo esc_attr( $captcha_token ); ?>" />
			</div>
			<div class="awi-field awi-field--serial">
				<label class="awi-label" for="awi-serial"><?php esc_html_e( 'شماره سریال', 'apple-warranty-inquiry' ); ?></label>
				<input
					type="text"
					class="awi-input"
					id="awi-serial"
					name="serial"
					dir="ltr"
					autocomplete="off"
					placeholder="<?php esc_attr_e( 'شماره سریال دستگاه خود را وارد کنید.', 'apple-warranty-inquiry' ); ?>"
				/>
			</div>
		</div>
		<div class="awi-form__actions">
			<button type="submit" class="awi-btn" id="awi-submit">
				<span class="awi-btn__spinner" aria-hidden="true"></span>
				<span class="awi-btn__text"><?php esc_html_e( 'بررسی شناسه', 'apple-warranty-inquiry' ); ?><svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.783 18.828a8.05 8.05 0 0 0 7.439-4.955 8.03 8.03 0 0 0-1.737-8.765 8.045 8.045 0 0 0-13.735 5.68c0 2.131.846 4.174 2.352 5.681a8.05 8.05 0 0 0 5.68 2.359m5.706-2.337 4.762 4.759" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
			</button>
			<?php if ( ! empty( $help_url ) ) : ?>
				<p class="awi-help">
					<a href="<?php echo esc_url( $help_url ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'چگونه شماره سریال دستگاه خود را پیدا کنم؟', 'apple-warranty-inquiry' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	</form>
	<div class="awi-result-wrap" id="awi-result" hidden></div>
</div>
