<?php
/**
 * [FR]  Page ajoutant l'AJAX' des boutons Call & Blame.
 * [ENG] This Php file contain AJAX for buttons Call & Blame.
 *
 * @package WordPress.
 * @subpackage Call & Blame.
 */

/**
 * Class gérant l'ajax du plugin..
 */
class Cm_Ajax_Admin {
	/**
	 * Fonction utilisé quand la class est appelé qui appelle les autres fonctions de la classe.
	 *
	 * @method __construct
	 */
	public function __construct() {
		add_action( 'wp_ajax_count_tel', array( $this, 'count_tel_callback' ), 105 );
		add_action( 'wp_ajax_count_tel_moins', array( $this, 'count_tel_moins_callback' ), 105 );
		add_action( 'wp_ajax_count', array( $this, 'count_callback' ), 105 );
		add_action( 'wp_ajax_form_call', array( $this, 'form_call_callback' ), 105 );
		add_action( 'wp_ajax_treated', array( $this, 'treated_callback' ), 105 );
		add_action( 'wp_ajax_display', array( $this, 'display_recall_callback' ), 105 );
		add_action( 'wp_ajax_display_deux', array( $this, 'display_will_recall_callback' ), 105 );
		add_action( 'wp_ajax_display_button_recall', array( $this, 'display_button_recall_callback' ), 105 );
		add_action( 'wp_ajax_display_button_will_recall', array( $this, 'display_button_will_recall_callback' ), 105 );
	}

	/**
	 * [FR] 	Action du bouton Call.
	 * [ENG]  Button Call's action.
	 *
	 * @method count_tel_callback Action du bouton.
	 */
	public function count_tel_callback() {
		$time_db = current_time( 'Ym' );
		$day = intval( current_time( 'd' ) );
		$select = get_user_meta( get_current_user_id(),'imputation_' . $time_db, true );
		$select[ $day ]['call']++;
		update_user_meta( get_current_user_id(), 'imputation_' . $time_db, $select );
		wp_die( esc_html( $select[ $day ]['call'] ) );
	}

	/**
	 * [FR] 	Action inverse du bouton Call.
	 * [ENG]  Button wich decrement the Call's total.
	 *
	 * @method count_tel_callback.
	 */
	public function count_tel_moins_callback() {
		$time_db = current_time( 'Ym' );
		$day = intval( current_time( 'd' ) );
		$select = get_user_meta( get_current_user_id(),'imputation_' . $time_db, true );
		$select[ $day ]['call']--;
		update_user_meta( get_current_user_id(), 'imputation_' . $time_db, $select );
		wp_die( esc_html( $select[ $day ]['call'] ) );
	}

	/**
	 * [FR]  Action du bouton Blame.
	 * [ENG] Button Blame's action.
	 *
	 * @method count_callback.
	 */
	public function count_callback() {
		$time_db = current_time( 'Ym' );
		$day = intval( current_time( 'd' ) );
		$select = get_user_meta( get_current_user_id(),'imputation_' . $time_db, true );
		$select[ $day ]['blame'][ $_GET['user_id'] ]++;
		update_user_meta( get_current_user_id(), 'imputation_' . $time_db, $select );
		$id_select = get_users( 'orderby=nicename&role=administrator&exclude=' . get_current_user_id() . '' );
		$total_blame = 0;
		foreach ( $id_select as $user ) {
			$x = $user->ID;
			$total_blame = $total_blame + $select[ $day ]['blame'][ $x ];
		}
		if ( ( ! isset( $select[ $day ]['blame']['0'] ) ) or ( ! isset( $select[ $day ]['blame']['999999'] ) ) ) {
			$select[ $day ]['blame']['0'] = 0;
			$select[ $day ]['blame']['999999'] = 0;
		}
		$total_blame = $total_blame + $select[ $day ]['blame']['0'];
		$total_blame = $total_blame + $select[ $day ]['blame']['999999'];
		$data = array(
			'count_current_user' => $select[ $day ]['blame'][ $_GET['user_id'] ],
			'total' => $total_blame,
			'id_user' => $_GET['user_id'],
		);
		wp_send_json_success( $data );
	}

	/**
	 * [FR]  Traitement du formulaire du bouton Call en POST et de la recherche en GET.
	 * [ENG] This function save the data from the dialog form of Call's button via POST and search from GET.
	 *
	 * @method form_call_callback.
	 */
	public function form_call_callback() {
		if ( ! empty( $_POST['_wpnonce_dialog'] ) && check_admin_referer( 'form_dialog_check', '_wpnonce_dialog' ) ) {
			if ( '' !== $_POST['button_call'] ) {
				$button_call = $_POST['button_call'];
			} else {
				$button_call = 'empty';
			}
			if ( '' !== $_POST['number_contact_call'] ) {
				$number_contact_call = $_POST['number_contact_call'];
			} else {
				$number_contact_call = 'empty_phone';
			}
			if ( '' !== $_POST['email_contact_call'] ) {
				$email_contact_call = $_POST['email_contact_call'];
			} else {
				$email_contact_call = 'empty_mail';
			}
			if ( '' !== $_POST['comment_content_call'] ) {
				$comment_content_call = $_POST['comment_content_call'];
			} else {
				$comment_content_call = "Réception d'un appel";
			}
			if ( '' !== $_POST['name_contact_call'] ) {
				$name_contact_call = $_POST['name_contact_call'];
			} else {
				$name_contact_call = 'empty_name';
			}
			if ( '' !== $_POST['society_contact_call'] ) {
				$society_contact_call = $_POST['society_contact_call'];
			} else {
				$society_contact_call = 'empty_society';
			}
			if ( '' !== $_POST['to_call'] ) {
				$to_call = $_POST['to_call'];
			} else {
				$to_call = 'empty';
			}


			// On ajoute une tâche.
			if ( ! empty( $_POST['user']['customer_id'] ) ) { // WPCS: CRSF ok.
				global $task_controller, $point_controller, $time_controller, $wpdb;

				$customer_id = wps_customer_ctr::get_customer_id_by_author_id( $_POST['user']['customer_id'] );

				$query = new WP_Query( array(
					'fields' => 'ids',
					'post_parent' => $customer_id,
					'post_type' => 'wpeo-task',
					'posts_per_page' => 1,
					'tax_query' => array(
						array(
							'taxonomy' => 'wpeo_tag',
							'field' => 'slug',
							'terms' => 'commercial',
						),
					),
				) );

				if ( 0 === $query->post_count ) {
					$task = $task_controller->create( array(
						'title' => __( 'Appel téléphonique', 'task-manager' ),
						'parent_id' => $customer_id,
						'author_id' => get_current_user_id(),
						'option' => array(
							'user_info' => array(
								'owner_id' => get_current_user_id(),
							),
						),
					) );

					$tag_selected = get_term_by( 'slug', 'commercial', 'wpeo_tag' );
					$task->taxonomy['wpeo_tag'][] = (int) $tag_selected->term_id;
					$task = $task_controller->update( $task );
				} else {
					$task = $task_controller->show( $query->posts[0] );
				}

				$comment_point = $name_contact_call . ' - ' . $society_contact_call . ' - ' . $number_contact_call . ' - ' . $email_contact_call;

				$comment = array(
					'post_id' => $task->id,
					'status' => '-34070',
					'content' => $comment_point,
					'comment_type' => 'phone_call',
					'user_id' => get_current_user_id(),
					'date' => current_time( 'mysql' ),
				);

				$point = $point_controller->create( $comment );
				$task->option['task_info']['order_point_id'][] = (int) $point->id;
				$task_controller->update( $task );

				$time_controller->create( array(
					'post_id' => $task->id,
					'parent_id' => $point->id,
					'status' => '-34070',
					'content' => $comment_content_call,
					'comment_type' => 'phone_call_comment',
					'user_id' => get_current_user_id(),
					'date' => current_time( 'mysql' ),
				) );

				// $point_id = $wpdb->get_var(
				// 	"SELECT comment_ID FROM $wpdb->comments
				// 	WHERE comment_content='Appel téléphonique'
				// 	AND comment_post_ID=" . $task->id );
				//
				// if ( empty( $point_id ) ) {
				// 	$point = $point_controller->create( array(
				// 		'author_id' => get_current_user_id(),
				// 		'status' => '-34070',
				// 		'date' => current_time( 'mysql' ),
				// 		'content' => 'Appel téléphonique',
				// 		'post_id' => $task->id,
				// 	) );
				//
				// 	/** Add to the order point */
				// 	$task->option['task_info']['order_point_id'][] = (int) $point->id;
				// 	$task_controller->update( $task );
				// } else {
				// 	$point = $point_controller->show( $point_id );
				// }
				//
				// $time_controller->create( $comment );
			}

			if ( 'recall' === $button_call ) {
				$cm_mail_sender = new Cm_Mail_Sender();
				$cm_mail_sender->send_mail( $to_call, array(
					'name' => $name_contact_call,
					'society' => $society_contact_call,
					'phone' => $number_contact_call,
					'email' => $email_contact_call,
				), $comment );
			}
		} elseif ( ! empty( $_GET['_wpnonce_dialog'] ) && check_admin_referer( 'form_dialog_check', '_wpnonce_dialog' ) ) {
			if ( ( '' !== $_GET['number_contact_call'] ) or ( '' !== $_GET['email_contact_call'] ) or ( '' !== $_GET['name_contact_call'] ) or ( '' !== $_GET['society_contact_call'] ) ) {
				$comment = array(
					'status' => array( 'treated', 'recall', 'transfered' ),
					'order' => 'ASC',
				);
				if ( '' !== $_GET['number_contact_call'] ) {
					$number_caller = $_GET['number_contact_call'];
					$comment['meta_key'] = '_eocm_caller_phone';
					$comment['meta_value'] = $number_caller;
				}
				if ( '' !== $_GET['email_contact_call'] ) {
					$mail_caller = $_GET['email_contact_call'];
					$comment['meta_key'] = '_eocm_caller_email';
					$comment['meta_value'] = $mail_caller;
				}
				if ( '' !== $_GET['name_contact_call'] ) {
					$name_caller = $_GET['name_contact_call'];
					$comment['meta_key'] = '_eocm_caller_name';
					$comment['meta_value'] = $name_caller;
				}
				if ( '' !== $_GET['society_contact_call'] ) {
					$society_caller = $_GET['society_contact_call'];
					$comment['meta_key'] = '_eocm_caller_society';
					$comment['meta_value'] = $society_caller;
				}
				$data_comment = get_comments( $comment );
				foreach ( $data_comment as $data ) {
					$id = $data->comment_ID;
					$name_caller = get_comment_meta( $id, '_eocm_caller_name', true );
					$society_caller = get_comment_meta( $id, '_eocm_caller_society', true );
					$number_caller = get_comment_meta( $id, '_eocm_caller_phone', true );
					$mail_caller = get_comment_meta( $id, '_eocm_caller_email', true );
					$comment_content_receive = get_comment( $id, ARRAY_A );
					$comment_content = $comment_content_receive['comment_content'];
				}
				$data = array(
					'name' => $name_caller,
					'society' => $society_caller,
					'mail' => $mail_caller,
					'number' => $number_caller,
					'commentcontent' => $comment_content,
				);
				wp_send_json_success( $data );
			} else {
				$data = array(
					'name' => '',
					'society' => '',
					'mail' => '',
					'number' => '',
					'commentcontent' => 'Remplissez un champ pour la recherche !',
				);
				wp_send_json_success( $data );
			}
			wp_die();
		}
		wp_die();
	}

	/**
	 * Fonction pour modifier les "Recall" en Traité.
	 *
	 * @method treated_callback
	 */
	public function treated_callback() {
		$treated['comment_approved'] = 'treated';
		$treated['comment_ID'] = $_GET['comment_id'];
		wp_update_comment( $treated );
		$new_comment = array(
			'comment_parent' => $_GET['comment_id'],
			'comment_approved' => 'treated',
			'comment_content' => $_GET['new_comment'],
			'user_id' => get_current_user_id(),
		);
		wp_insert_comment( $new_comment );
		wp_die();
	}



	/**
	 * Bouton Recall qui ne s'affiche que quand vous avez une personne à rappeler.
	 *
	 * @method cm_recall.
	 * @param  mixed $wp_admin_bar WordPress function for addding node.
	 */
	public function display_button_recall_callback( $wp_admin_bar ) {
		$user_data = get_userdata( get_current_user_id() );
		if ( 'administrator' === implode( ', ', $user_data->roles ) ) {
			$select_comment = array(
				'meta_key' => '_eocm_receiver_id',
				'meta_value' => get_current_user_id(),
				'status' => 'recall',
				'count' => true,
			);
			$selected_comment = get_comments( $select_comment );
			if ( $selected_comment > 0 ) {
				$data = array(
					'retour' => 'oui',
				);
				wp_send_json_success( $data );
			} elseif ( 0 === $selected_comment ) {
				$data = array(
					'retour' => 'non',
				);
				wp_send_json_success( $data );
			}
		}
	}

	/**
	 * Bouton Will-recall qui ne s'affiche que quand des personnes veulent vous rappeler.
	 *
	 * @method display_button_will_recall_callback
	 * @param  mixed $wp_admin_bar WordPress function for addding node.
	 */
	public function display_button_will_recall_callback( $wp_admin_bar ) {
		$user_data = get_userdata( get_current_user_id() );
		if ( 'administrator' === implode( ', ', $user_data->roles ) ) {
			$select_comment = array(
				'meta_key' => '_eocm_receiver_id',
				'meta_value' => get_current_user_id(),
				'status' => 'will_recall',
				'count' => true,
			);
			$selected_comment = get_comments( $select_comment );
			if ( $selected_comment > 0 ) {
				$data = array(
					'return' => 'yes',
				);
				wp_send_json_success( $data );
			} elseif ( 0 === $selected_comment ) {
				$data = array(
					'return' => 'no',
				);
				wp_send_json_success( $data );
			}
		}
	}

	/**
	 * [display_deux_callback description]
	 *
	 * @method display_deux_callback
	 */
	public function display_recall_callback() {
		$comment = array(
			'meta_key' => '_eocm_receiver_id',
			'meta_value' => get_current_user_id(),
			'order' => 'ASC',
		);
		$comment['status'] = array( 'recall', 'will_recall' );
		$data_comment = get_comments( $comment );
		foreach ( $data_comment as $comments ) {
			$comments->date_comment = get_comment_date( '', $comments->comment_ID );
			$comments->name_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_name', true );
			$comments->society_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_society', true );
			$comments->phone_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_phone', true );
			$comments->mail_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_email', true );
			$comments->url = admin_url( 'admin-ajax.php?action=treated&comment_id=' . $comments->comment_ID );
		}
		include( plugin_dir_path( __FILE__ ) . 'views/dialog-child.php' );
		wp_die();
	}

	/**
	 * [display_recall_callback description]
	 *
	 * @method display_recall_callback
	 */
	public function display_will_recall_callback() {
		$comment = array(
			'meta_key' => '_eocm_receiver_id',
			'meta_value' => get_current_user_id(),
			'order' => 'ASC',
		);
		$comment['status'] = 'will_recall';
		$data_comment = get_comments( $comment );
		foreach ( $data_comment as $comments ) {
			$comments->date_comment = get_comment_date( '', $comments->comment_ID );
			$comments->name_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_name', true );
			$comments->society_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_society', true );
			$comments->phone_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_phone', true );
			$comments->mail_caller = get_comment_meta( $comments->comment_ID, '_eocm_caller_email', true );
			$comments->url = admin_url( 'admin-ajax.php?action=treated&comment_id=' . $comments->comment_ID );
		}
		include( plugin_dir_path( __FILE__ ) . 'views/dialog-child.php' );
		wp_die();
	}
}

new Cm_Ajax_Admin();
