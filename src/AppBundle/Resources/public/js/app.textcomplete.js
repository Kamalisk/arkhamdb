(function app_textcomplete(textcomplete, $) {

var icons = 'guardian survivor rogue mystic seeker action reaction fast free unique per_investigator null elder_sign elder_thing auto_fail skull cultist lightning tablet willpower intellect combat agility wild'.split(' ');

var rules = [
	{key:"Intro", value:"The Thing That Should Not Be..."},
	{key:"The_Golden_Rules", value:"The Golden Rules"},
	{key:"The_Grim_Rule", value:"The Grim Rule"},
	{key:"Glossary", value:"Glossary"},
	{key:"A_An", value:"A, An"},
	{key:"Ability", value:"Ability"},
	{key:"Abilities_Constant", value:"Constant Abilities"},
	{key:"Abilities_Forced", value:"Forced Abilities"},
	{key:"Abilities_Revelation", value:"Revelation Abilities"},
	{key:"Abilities_Triggered", value:"Triggered Abilities"},
	{key:"Abilities_Keywords", value:"Keywords"},
	{key:"Spawn_Instructions_and_Prey_Instructions", value:"Spawn Instructions and Prey Instructions"},
	{key:"Action_Designators", value:"Action Designators"},
	{key:"Act_Deck_and_Agenda_Deck", value:"Act Deck and Agenda Deck"},
	{key:"Action", value:"Action"},
	{key:"Activate_Action", value:"Activate Action"},
	{key:"Active_Player", value:"Active Player"},
	{key:"Additional_Costs", value:"Additional Costs"},
	{key:"After", value:"After"},
	{key:"Agenda_Deck", value:"Agenda Deck"},
	{key:"Alert", value:"Alert"},
	{key:"Aloof", value:"Aloof"},
	{key:"Asset_Cards", value:"Asset Cards"},
	{key:"At", value:"At"},
	{key:"Attach_To", value:"Attach To"},
	{key:"Attacker_Attacked", value:"Attacker, Attacked"},
	{key:"Attack_of_Opportunity", value:"Attack of Opportunity"},
	{key:"Automatic_Failure_Success", value:"Automatic Failure/Success"},
	{key:"Base_Value", value:"Base Value"},
	{key:"Bearer", value:"Bearer"},
	{key:"Blank", value:"Blank"},
	{key:"Campaign_Play", value:"Campaign Play"},
	{key:"Defeat_by_Card_Ability", value:"Defeat by Card Ability"},
	{key:"Advancing_to_Next_Scenario", value:"Advancing to Next Scenario"},
	{key:"Joining_or_Leaving_a_Campaign", value:"Joining or Leaving a Campaign"},
	{key:"Cancel", value:"Cancel"},
	{key:"Cannot", value:"Cannot"},
	{key:"Cardtypes", value:"Cardtypes"},
	{key:"Chaos_Tokens", value:"Chaos Tokens"},
	{key:"Choices_and_the_Grim_Rule", value:"Choices, and the Grim Rule"},
	{key:"Clues", value:"Clues"},
	{key:"Collection", value:"Collection"},
	{key:"Constant_Abilities", value:"Constant Abilities"},
	{key:"Control", value:"Control"},
	{key:"Copy", value:"Copy"},
	{key:"Costs", value:"Costs"},
	{key:"Dealing_Damage_Horror", value:"Dealing Damage/Horror"},
	{key:"Deck", value:"Deck"},
	{key:"Deckbuilding", value:"Deckbuilding"},
	{key:"Classes", value:"Classes"},
	{key:"Defeat", value:"Defeat"},
	{key:"Delayed_Effects", value:"Delayed Effects"},
	{key:"Difficulty_level", value:"Difficulty (level)"},
	{key:"Difficulty_skill_tests", value:"Difficulty (skill tests)"},
	{key:"Direct_Damage_Direct_Horror", value:"Direct Damage, Direct Horror"},
	{key:"Discard_Piles", value:"Discard Piles"},
	{key:"Doom", value:"Doom"},
	{key:"Draw_Action", value:"Draw Action"},
	{key:"Drawing_Cards", value:"Drawing Cards"},
	{key:"Effects", value:"Effects"},
	{key:"Elimination", value:"Elimination"},
	{key:"Empty_Location", value:"Empty Location"},
	{key:"Encounter_Deck", value:"Encounter Deck"},
	{key:"Encounter_Set", value:"Encounter Set"},
	{key:"Enemy_Cards", value:"Enemy Cards"},
	{key:"Enemy_Engagement", value:"Enemy Engagement"},
	{key:"Engage_Action", value:"Engage Action"},
	{key:"Enters_Play", value:"Enters Play"},
	{key:"Evade_Action", value:"Evade, Evade Action"},
	{key:"Event_Cards", value:"Event Cards"},
	{key:"Exceptional", value:"Exceptional"},
	{key:"Exhaust", value:"Exhaust, Exhausted"},
	{key:"Exile", value:"Exile"},
	{key:"Experience", value:"Experience"},
	{key:"Fast", value:"Fast"},
	{key:"Fight_Action", value:"Fight Action"},
	{key:"Flavor_Text", value:"Flavor Text"},
	{key:"Forced_Abilities", value:"Forced Abilities"},
	{key:"Gains", value:"Gains"},
	{key:"Game", value:"Game"},
	{key:"Hand_Size", value:"Hand Size"},
	{key:"Haunted", value:"Haunted"},
	{key:"Heal", value:"Heal"},
	{key:"Health_and_Damage", value:"Health and Damage"},
	{key:"Hidden", value:"Hidden"},
	{key:"Hunter", value:"Hunter"},
	{key:"If", value:"If"},
	{key:"Immune", value:"Immune"},
	{key:"In_Play_and_Out_of_Play", value:"In Play and Out of Play"},
	{key:"In_Player_Order", value:"In Player Order"},
	{key:"Instead", value:"Instead"},
	{key:"Investigate_Action", value:"Investigate Action"},
	{key:"Investigation_Phase", value:"Investigation Phase"},
	{key:"Investigator_Deck", value:"Investigator Deck"},
	{key:"Keywords", value:"Keywords"},
	{key:"Killed_Insane_Investigators", value:"Killed/Insane Investigators"},
	{key:"Lasting_Effects", value:"Lasting Effects"},
	{key:"Lead_Investigator", value:"Lead Investigator"},
	{key:"Leaves_Play", value:"Leaves Play"},
	{key:"Limits_and_Maximums", value:"Limits and Maximums"},
	{key:"Location_Cards", value:"Location Cards"},
	{key:"Massive", value:"Massive"},
	{key:"May", value:"May"},
	{key:"Modifiers", value:"Modifiers"},
	{key:"Move", value:"Move"},
	{key:"Move_Action", value:"Move Action"},
	{key:"Mulligan", value:"Mulligan"},
	{key:"Must", value:"Must"},
	{key:"Nearest", value:"Nearest"},
	{key:"Nested_Sequences", value:"Nested Sequences"},
	{key:"Ownership_and_Control", value:"Ownership and Control"},
	{key:"Parley", value:"Parley"},
	{key:"Per_Investigator", value:"Per Investigator"},
	{key:"Peril", value:"Peril"},
	{key:"Permanent", value:"Permanent"},
	{key:"Play", value:"Play"},
	{key:"Play_Action", value:"Play Action"},
	{key:"Play_Restrictions_Permissions_and_Instructions", value:"Play Restrictions, Permissions, and Instructions"},
	{key:"Prey", value:"Prey"},
	{key:"Printed", value:"Printed"},
	{key:"Priority_of_Simultaneous_Resolution", value:"Priority of Simultaneous Resolution"},
	{key:"Put_into_Play", value:"Put into Play"},
	{key:"Qualifiers", value:"Qualifiers"},
	{key:"Reaction_Opportunities", value:"Reaction Opportunities"},
	{key:"Ready", value:"Ready"},
	{key:"Record_in_your_Campaign Log", value:"Record in your Campaign Log..."},
	{key:"Remember_that", value:"Remember that..."},
	{key:"Removed_from_Game", value:"Removed from Game"},
	{key:"Resign", value:"Resign"},
	{key:"Resource_Action", value:"Resource Action"},
	{key:"Resources", value:"Resources"},
	{key:"Retaliate", value:"Retaliate"},
	{key:"Revelation", value:"Revelation"},
	{key:"Sanity_and_Horror", value:"Sanity and Horror"},
	{key:"Set_Aside", value:"Set Aside"},
	{key:"Seal", value:"Seal"},
	{key:"Search", value:"Search"},
	{key:"Self_Referential_Text", value:"Self-Referential Text"},
	{key:"Signature_Cards", value:"Signature Cards"},
	{key:"Skill_Cards", value:"Skill Cards"},
	{key:"Skill_Tests", value:"Skill Tests"},
	{key:"Slots", value:"Slots"},
	{key:"Spawn", value:"Spawn"},
	{key:"Standalone_Mode", value:"Standalone Mode"},
	{key:"Supplies", value:"Supplies"},
	{key:"Surge", value:"Surge"},
	{key:"Taking_Damage_Horror", value:"Taking Damage/Horror"},
	{key:"Target", value:"Target"},
	{key:"Then", value:"Then"},
	{key:"Threat_Area", value:"Threat Area"},
	{key:"Tokens", value:"Tokens, Running out of"},
	{key:"Traits", value:"Traits"},
	{key:"Trauma", value:"Trauma"},
	{key:"Treachery_Cards", value:"Treachery Cards"},
	{key:"Triggered_Abilities", value:"Triggered Abilities"},
	{key:"Triggering_Condition", value:"Triggering Condition"},
	{key:"Unique", value:"Unique"},
	{key:"Upkeep_Phase", value:"Upkeep Phase"},
	{key:"Uses", value:"Uses (X 'type')"},
	{key:"Vengeance_Points", value:"Vengeance Points"},
	{key:"Victory_Display_Victory_Points", value:"Victory Display, Victory Points"},
	{key:"Weakness", value:"Weakness"},
	{key:"When", value:"When"},
	{key:"Winning_and_Losing", value:"Winning and Losing"},
	{key:"The_letter_X", value:"The letter X"},
	{key:"You_Your", value:"You/Your"},
	{key:"Appendix_I_Initiation_Sequence", value:"Initiation Sequence"},
	{key:"Appendix_II_Timing_and_Gameplay", value:"Timing and Gameplay"},
	{key:"Phase_Sequence_Timing", value:"Phase Sequence Timing"},
	{key:"Framework_Event_Details", value:"Framework Event Details"},
	{key:"Mythos_Phase", value:"Mythos phase"},
	{key:"Enemy_Phase", value:"Enemy phase"},
	{key:"Skill_Test_Timing", value:"Skill Test Timing"},
	{key:"Appendix_III_Setting_Up_The_Game", value:"Setting Up The Game"},
	{key:"Appendix_IV_Card_Anatomy", value:"Card Anatomy"},
	{key:"Scenario_Card_Anatomy_Key", value:"Scenario Card Anatomy Key"},
	{key:"Player_Card_Anatomy_Key", value:"Player Card Anatomy Key"}
];

/**
 * options: cards, icons, users
 */
textcomplete.setup = function setup(textarea, options) {

	options = _.extend({cards: true, icons: true, users: false, rules: true}, options);

	var actions = [];

	if(options.cards) {
		actions.push({
			match : /\B#([\-+\w]*)$/,
			search : function(term, callback) {
				var regexp = new RegExp('\\b' + term, 'i');
				callback(app.data.cards.find({
					name : regexp
				}));
			},
			template : function(value) {
				return value.name;
			},
			replace : function(value) {
				return '[' + value.name + ']('
						+ Routing.generate('cards_zoom', {card_code:value.code})
						+ ')';
			},
			index : 1
		})
	}

	if(options.icons) {
		actions.push({
			match : /\$([\-+\w]*)$/,
			search : function(term, callback) {
				var regexp = new RegExp('^' + term, 'i');
				callback(_.filter(icons,
					function(symbol) { return regexp.test(symbol); }
				));
			},
			template : function(value) {
				return value;
			},
			replace : function(value) {
				return '<span class="icon-' + value + '"></span>';
			},
			index : 1
		});
	}
	
	if(options.rules) {
		actions.push({
			match : /\^([\-+\w]*)$/,
			search : function(term, callback) {
				var regexp = new RegExp('^' + term, 'i');
				callback(_.filter(rules,
					function(rule) { return regexp.test(rule.value); }
				));
			},
			template : function(value) {
				return value.value;
			},
			replace : function(value) {
				return '[' + value.value + ']('
						+ Routing.generate('rules') + '#'+value.key
						+ ')';
			},
			index : 1
		});
	}

	if(options.users) {
		actions.push({
			match : /\B@([\-+\w]*)$/,
			search : function(term, callback) {
				var regexp = new RegExp('^' + term, 'i');
				callback($.grep(options.users, function(user) {
					return regexp.test(user);
				}));
			},
			template : function(value) {
				return value;
			},
			replace : function(value) {
				return '`@' + value + '`';
			},
			index : 1
		});
	}

	$(textarea).textcomplete(actions);

}

})(app.textcomplete = {}, jQuery);
