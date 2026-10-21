<?php
/**
 * Custom JSON-LD modes: how a post's custom entities combine with the
 * generated ones.
 *
 * - `add` appends them to the generated graph.
 * - `override` lets them replace generated entities with the same `@id` or role.
 * - `replace` outputs only them.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * @return string[]
 */
function better_schema_custom_modes(): array {
	return array( 'add', 'override', 'replace' );
}

/**
 * Role of the entities each built-in piece generates.
 *
 * Pieces without a role, such as `author` or pieces added by other plugins,
 * can only be replaced by a custom entity with the same `@id`.
 *
 * @return array<string, string> Map of piece id to role.
 */
function better_schema_piece_roles(): array {
	$roles = array( 'website', 'identity', 'webpage', 'breadcrumb', 'article', 'product', 'event', 'recipe', 'faq', 'video' );

	return array_combine( $roles, $roles );
}

/**
 * Schema.org types that fill each role, including their subtypes.
 *
 * Subtypes are taken from the schema.org vocabulary (schemaorg-current-https,
 * October 2026). A Person only fills the identity role when the site identity is
 * a person, so an author never replaces an organization.
 *
 * Filter `better_schema_custom_role_types` receives the map and returns a map.
 *
 * @return array<string, string[]> Map of role to types.
 */
function better_schema_custom_role_types(): array {
	$identity = array(
		'Organization', 'AccountingService', 'AdultEntertainment', 'Airline', 'AmusementPark', 'AnimalShelter',
		'ArchiveOrganization', 'ArtGallery', 'Attorney', 'AutoBodyShop', 'AutoDealer', 'AutoPartsStore',
		'AutoRental', 'AutoRepair', 'AutoWash', 'AutomatedTeller', 'AutomotiveBusiness', 'Bakery',
		'BankOrCreditUnion', 'BarOrPub', 'BeautySalon', 'BedAndBreakfast', 'BikeStore', 'BookStore', 'BowlingAlley',
		'Brewery', 'CafeOrCoffeeShop', 'Campground', 'Casino', 'ChildCare', 'ClothingStore', 'CollegeOrUniversity',
		'ComedyClub', 'ComputerStore', 'Consortium', 'ConvenienceStore', 'Cooperative', 'Corporation',
		'CovidTestingFacility', 'DanceGroup', 'DaySpa', 'Dentist', 'DepartmentStore', 'DiagnosticLab', 'Distillery',
		'DryCleaningOrLaundry', 'EducationalOrganization', 'Electrician', 'ElectronicsStore', 'ElementarySchool',
		'EmergencyService', 'EmploymentAgency', 'EntertainmentBusiness', 'ExerciseGym', 'FastFoodRestaurant',
		'FinancialService', 'FireStation', 'Florist', 'FoodEstablishment', 'FundingAgency', 'FundingScheme',
		'FurnitureStore', 'GardenStore', 'GasStation', 'GeneralContractor', 'GolfCourse', 'GovernmentOffice',
		'GovernmentOrganization', 'GroceryStore', 'HVACBusiness', 'HairSalon', 'HardwareStore',
		'HealthAndBeautyBusiness', 'HealthClub', 'HighSchool', 'HobbyShop', 'HomeAndConstructionBusiness',
		'HomeGoodsStore', 'Hospital', 'Hostel', 'Hotel', 'HousePainter', 'IceCreamShop', 'IndividualPhysician',
		'InsuranceAgency', 'InternetCafe', 'JewelryStore', 'LegalService', 'Library', 'LibrarySystem', 'LiquorStore',
		'LocalBusiness', 'Locksmith', 'LodgingBusiness', 'MedicalBusiness', 'MedicalClinic', 'MedicalOrganization',
		'MensClothingStore', 'MiddleSchool', 'MobilePhoneStore', 'Motel', 'MotorcycleDealer', 'MotorcycleRepair',
		'MovieRentalStore', 'MovieTheater', 'MovingCompany', 'MusicGroup', 'MusicStore', 'NGO', 'NailSalon',
		'NewsMediaOrganization', 'NightClub', 'Notary', 'OfficeEquipmentStore', 'OnlineBusiness',
		'OnlineMarketplace', 'OnlineStore', 'Optician', 'OutletStore', 'PawnShop', 'PerformingGroup', 'PetStore',
		'Pharmacy', 'Physician', 'PhysiciansOffice', 'Plumber', 'PoliceStation', 'PoliticalParty', 'PostOffice',
		'Preschool', 'ProfessionalService', 'Project', 'PublicSwimmingPool', 'RadioStation', 'RealEstateAgent',
		'RecyclingCenter', 'ResearchOrganization', 'ResearchProject', 'Resort', 'Restaurant', 'RoofingContractor',
		'School', 'SearchRescueOrganization', 'SelfStorage', 'ShoeStore', 'ShoppingCenter', 'SkiResort',
		'SportingGoodsStore', 'SportsActivityLocation', 'SportsClub', 'SportsOrganization', 'SportsTeam',
		'StadiumOrArena', 'Store', 'TattooParlor', 'TelevisionStation', 'TennisComplex', 'TheaterGroup', 'TireShop',
		'TouristInformationCenter', 'ToyStore', 'TravelAgency', 'VacationRental', 'VeterinaryCare', 'WholesaleStore',
		'Winery', 'WorkersUnion',
	);

	if ( 'Person' === better_schema_get_identity()['type'] ) {
		$identity[] = 'Person';
	}

	$types = array(
		'website'    => array( 'WebSite' ),
		'identity'   => $identity,
		// FAQPage is a WebPage subtype, but the plugin generates it as a separate entity.
		'webpage'    => array(
			'WebPage', 'AboutPage', 'CheckoutPage', 'CollectionPage', 'ContactPage', 'ImageGallery', 'ItemPage',
			'MediaGallery', 'MedicalWebPage', 'ProfilePage', 'QAPage', 'RealEstateListing', 'SearchResultsPage',
			'VideoGallery',
		),
		'breadcrumb' => array( 'BreadcrumbList' ),
		'article'    => array(
			'Article', 'APIReference', 'AdvertiserContentArticle', 'AnalysisNewsArticle', 'AskPublicNewsArticle',
			'BackgroundNewsArticle', 'BlogPosting', 'DiscussionForumPosting', 'LiveBlogPosting',
			'MedicalScholarlyArticle', 'NewsArticle', 'OpinionNewsArticle', 'Report', 'ReportageNewsArticle',
			'ReviewNewsArticle', 'SatiricalArticle', 'ScholarlyArticle', 'SocialMediaPosting', 'TechArticle',
		),
		'product'    => array(
			'Product', 'BusOrCoach', 'Car', 'DietarySupplement', 'Drug', 'IndividualProduct', 'Motorcycle',
			'MotorizedBicycle', 'ProductCollection', 'ProductGroup', 'ProductModel', 'SomeProducts', 'Vehicle',
		),
		'event'      => array(
			'Event', 'BroadcastEvent', 'BusinessEvent', 'ChildrensEvent', 'ComedyEvent', 'ConferenceEvent',
			'CourseInstance', 'DanceEvent', 'DeliveryEvent', 'EducationEvent', 'EventSeries', 'ExhibitionEvent',
			'Festival', 'FoodEvent', 'Hackathon', 'LiteraryEvent', 'MusicEvent', 'OnDemandEvent', 'PerformingArtsEvent',
			'PublicationEvent', 'SaleEvent', 'ScreeningEvent', 'SocialEvent', 'SportsEvent', 'TheaterEvent',
			'VisualArtsEvent',
		),
		'recipe'     => array( 'Recipe' ),
		'faq'        => array( 'FAQPage' ),
		'video'      => array( 'VideoObject' ),
	);

	$types = apply_filters( 'better_schema_custom_role_types', $types );

	return is_array( $types ) ? $types : array();
}

/**
 * Finds the role a custom entity fills from its `@type`.
 *
 * @param array                   $entity     Custom entity.
 * @param array<string, string[]> $role_types Map from `better_schema_custom_role_types()`.
 * @return string|null Role, or null when the entity fills none.
 */
function better_schema_custom_entity_role( array $entity, array $role_types ): ?string {
	$types = $entity['@type'] ?? null;

	foreach ( is_array( $types ) ? $types : array( $types ) as $type ) {
		if ( ! is_string( $type ) ) {
			continue;
		}

		$type = (string) preg_replace( '#^(schema:|https?://schema\.org/)#i', '', $type );

		foreach ( $role_types as $role => $role_list ) {
			if ( is_array( $role_list ) && in_array( $type, $role_list, true ) ) {
				return $role;
			}
		}
	}

	return null;
}

/**
 * Lets custom entities replace the generated entities they describe.
 *
 * A custom entity replaces the generated entity with the same `@id` or,
 * failing that, the first generated entity with the same role. It takes that
 * entity's place in the graph, and references to the replaced `@id` are
 * rewritten so the graph stays connected. A custom entity without an `@id`
 * keeps the replaced one. Custom entities that replace nothing are appended.
 *
 * @param array<int, array{0: string, 1: array}> $entries Piece id and entity pairs, in graph order.
 * @return array<int, array> Entities.
 */
function better_schema_merge_custom_entities( array $entries ): array {
	$piece_roles = better_schema_piece_roles();
	$role_types  = better_schema_custom_role_types();
	$graph       = array();
	$roles       = array();
	$custom      = array();

	foreach ( $entries as list( $piece_id, $entity ) ) {
		if ( 'custom' === $piece_id ) {
			$custom[] = $entity;
			continue;
		}

		$roles[ count( $graph ) ] = $piece_roles[ $piece_id ] ?? null;
		$graph[]                  = $entity;
	}

	$replaced  = array();
	$id_map    = array();
	$unmatched = array();

	foreach ( $custom as $entity ) {
		$id    = isset( $entity['@id'] ) && is_string( $entity['@id'] ) && '' !== $entity['@id'] ? $entity['@id'] : null;
		$index = null === $id ? null : better_schema_find_entity_by_id( $graph, $replaced, $id );

		if ( null === $index ) {
			$role  = better_schema_custom_entity_role( $entity, $role_types );
			$index = null === $role ? false : array_search( $role, array_diff_key( $roles, $replaced ), true );
			$index = false === $index ? null : $index;
		}

		if ( null === $index ) {
			$unmatched[] = $entity;
			continue;
		}

		$old_id = $graph[ $index ]['@id'] ?? null;

		if ( null === $id && is_string( $old_id ) ) {
			$entity = array( '@type' => $entity['@type'], '@id' => $old_id ) + $entity;
		} elseif ( null !== $id && is_string( $old_id ) && $old_id !== $id ) {
			$id_map[ $old_id ] = $id;
		}

		$graph[ $index ]    = $entity;
		$replaced[ $index ] = true;
	}

	return better_schema_rewrite_references( array_merge( $graph, $unmatched ), $id_map );
}

/**
 * Finds a generated entity by `@id`, comparing its public URL too, so a custom
 * entity written with the frontend URL of a headless site still matches.
 *
 * @param array<int, array> $graph    Generated entities.
 * @param array<int, true>  $replaced Indexes already replaced.
 * @param string            $id       Custom entity's `@id`.
 */
function better_schema_find_entity_by_id( array $graph, array $replaced, string $id ): ?int {
	foreach ( $graph as $index => $entity ) {
		$entity_id = $entity['@id'] ?? null;

		if ( isset( $replaced[ $index ] ) || ! is_string( $entity_id ) ) {
			continue;
		}

		if ( $entity_id === $id || better_schema_public_url( $entity_id ) === $id ) {
			return $index;
		}
	}

	return null;
}

/**
 * Points nested `@id` references at new ids. An entity's own `@id` is not changed.
 *
 * @param array<int, array>     $graph  Entities.
 * @param array<string, string> $id_map Map of old `@id` to new `@id`.
 * @return array<int, array>
 */
function better_schema_rewrite_references( array $graph, array $id_map ): array {
	if ( ! $id_map ) {
		return $graph;
	}

	foreach ( $graph as $index => $entity ) {
		if ( ! is_array( $entity ) ) {
			continue;
		}

		foreach ( $entity as $key => $value ) {
			if ( '@id' !== $key && is_array( $value ) ) {
				$graph[ $index ][ $key ] = better_schema_rewrite_reference_values( $value, $id_map );
			}
		}
	}

	return $graph;
}

/**
 * @param array                 $values Property value.
 * @param array<string, string> $id_map Map of old `@id` to new `@id`.
 */
function better_schema_rewrite_reference_values( array $values, array $id_map ): array {
	foreach ( $values as $key => $value ) {
		if ( '@id' === $key && is_string( $value ) && isset( $id_map[ $value ] ) ) {
			$values[ $key ] = $id_map[ $value ];
		} elseif ( is_array( $value ) ) {
			$values[ $key ] = better_schema_rewrite_reference_values( $value, $id_map );
		}
	}

	return $values;
}
