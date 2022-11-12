import DashboardModals from '@justwpforms/core/jsx/src/admin/dashboard-modals';
import { SlotFillProvider, Button, Modal, Guide, Popover, Notice, ExternalLink, TextControl, CheckboxControl, BaseControl } from '@wordpress/components';
import { useState, useReducer, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

( function( $, settings ) {

	/**
	 *
	 * Subscription modal
	 *
	 */
	const SubscribeModal = ( props ) => {
		const imageURL = `${settings.pluginURL}/inc/assets/svg/register.svg`;

		const initialState = {
			email: '',
			registrationKey: '',
			step: 'request_key',
			notice: null,
			disabled: false,
		};

		const reducer = ( state, newState ) => {
			return { ...state, ...newState };
		};

		const [state, dispatch] = useReducer(reducer, initialState);

		const requestKey = () => {
			dispatch( {
				notice: null,
				disabled: true,
			} );

			if ( '' === state.email.trim() || state.email.indexOf( '@' ) < 0 ) {
				dispatch( {
					disabled: false,
					notice: {
						status: 'error',
						message: __( 'Please enter an email address.', 'justwpforms' ),
					},
					step: 'request_key',
				} );

				return;
			}

			$.post( ajaxurl, {
				action: settings.subscribeModalActionRequestKey,
				_wpnonce: settings.subscribeModalNonceRequestKey,
				product_plan: settings.subscribeModalProductPlan,
				email: state.email,
			}, function( response ) {
				dispatch( {
					disabled: false,
					notice: {
						status: response.success ? 'success' : 'error',
						message: response.data,
					},
					step: response.success ? 'register_key' : 'request_key',
				} );
			} );
		};

		const registerKey = () => {
			dispatch( {
				notice: null,
				disabled: true,
			} );

			$.post( ajaxurl, {
				action: settings.subscribeModalActionAuthorize,
				_wpnonce: settings.subscribeModalNonceAuthorize,
				product_plan: settings.subscribeModalProductPlan,
				license_key: state.registrationKey,
			}, function( response ) {
				if ( response.success ) {
					return props.onRequestCloseAndRemoveBadge();
				}

				dispatch( {
					disabled: false,
					notice: {
						status: response.success ? 'success' : 'error',
						message: response.data,
					},
				} );
			} );
		}

		const getNotice = () => {
			if ( ! state.notice ) {
				return <></>
			} else {
				return <Notice status={ state.notice.status } isDismissible={ false }>{ state.notice.message }</Notice>
			}
		};

		const getStep = () => {
			switch( state.step ) {
				case 'request_key':
					return(
						<>
						<div className="justwpforms-modal__body">
							<label>{ __( 'Email address', 'justwpforms' ) }</label>
							<input 
								type="email" 
								value={ state.email } 
								onChange={ ( e ) => { dispatch( { email: e.target.value } ) } }
								disabled={ state.disabled }
								autoFocus
							/>
						</div>
						<div className="justwpforms-modal__footer">
							<BaseControl
								help={ <>
										{ __( 'Know your registration key?', 'justwpforms' ) } <Button
											isLink={ true } 
											onClick={ () => dispatch( { notice: null, step: 'register_key', email: '', } ) } 
											text={ __( 'Jump ahead', 'justwpforms' ) } />
									</> }
							>
								<div className="justwpforms-modal__footer-button-group">
									<Button 
										isPrimary={ true } 
										onClick={ requestKey } 
										text={ __( 'Send Registration Key', 'justwpforms' ) }
										disabled={ state.disabled }
										className="button-hero"
										key="button-request-key"
									/>
								</div>
							</BaseControl>
						</div>
						</>
					);

				case 'register_key':
					return(
						<>
						<div className="justwpforms-modal__body">
							<label>{ __( 'Registration key', 'justwpforms' ) }</label>
							<div className="hf-pwd">
								<input 
									type="password"
									className="justwpforms-credentials-input"
									value={ state.registrationKey } 
									onChange={ ( e ) => { dispatch( { registrationKey: e.target.value } ) } }
									disabled={ state.disabled }
									autoFocus
								/>
								<button type="button" className="button button-secondary hf-hide-pw hide-if-no-js" data-toggle="0" aria-label={ __( 'Show credentials', 'justwpforms' ) }>
									<span className="dashicons dashicons-visibility" aria-hidden="true"></span>
								</button>
							</div>
						</div>
						<div className="justwpforms-modal__footer">
							<BaseControl
								help={ state.email !== '' && ( <>
										{ __( 'Still no email?', 'justwpforms' ) } <Button
											isLink={ true } 
											onClick={ requestKey } 
											text={ __( 'Resend', 'justwpforms' ) } />
									</> ) } >
								<div className="justwpforms-modal__footer-button-group">
									<Button
										isSecondary={ true } 
										onClick={ () => dispatch( { disabled: false, notice: null, step: 'request_key' } ) } 
										text={ __( 'Cancel', 'justwpforms' ) }
										disabled={ state.disabled }
										key="button-cancel"
									/>
									<Button
										isPrimary={ true } 
										onClick={ registerKey } 
										text={ __( 'Register', 'justwpforms' ) }
										disabled={ state.disabled }
										key="button-register-key"
									/>
								</div>
							</BaseControl>
						</div>
						</>
					);
			}
		};

		return(
			<Guide
				onFinish={ props.onRequestCloseAndRedirect }
				className="justwpforms-modal justwpforms-modal--subscribe"
				pages={ [
					{
						image: (
							<picture>
								<img src={imageURL} />
							</picture>
						),
						content: (
							<>
							{ getNotice() }
							<div className="justwpforms-modal__header">
								<h1>{ __( 'You\'re unregistered', 'justwpforms' ) }</h1>
								<p>
									{ __( 'Add your email address connected with your account and we\'ll send you a registration key. If your membership has expired or your free trial has ended', 'justwpforms' ) }, <ExternalLink
										href="https://justwpforms.memberful.com/account/subscriptions">{ __( 'renew immediately to continue', 'justwpforms' ) }
										</ExternalLink>
								</p>
							</div>
							{ getStep() }
							</>
						),
					},
				] }
			/>
		);
	}

	const DashboardModalsBaseClass = DashboardModals( $, settings );
	
	class DashboardModalsClass extends DashboardModalsBaseClass {

		openSubscribeModal() {
			var modal = (
				<SubscribeModal 
					onRequestCloseAndRedirect={ this.closeSubscribeModalAndRedirect.bind( this ) } 
					onRequestClose={ this.closeModal.bind( this, 'subscribe' ) }
					onRequestCloseAndRemoveBadge={ this.closeSubscribeModalAndRemoveBadge.bind( this ) } />
			);

			this.openModal( modal );
		}

		closeSubscribeModalAndRedirect() {
			window.location.href = settings.dashboardURL;
		}

		closeSubscribeModalAndRemoveBadge() {
			$( '.justwpforms-unregistered-badge' ).hide();
			this.closeModal( 'subscribe' );
		}

	};

	var justwpforms = window.justwpforms || {};
	window.justwpforms = justwpforms;
	
	justwpforms.modals = new DashboardModalsClass();

} )( jQuery, _justwpformsDashboardModalsSettings );