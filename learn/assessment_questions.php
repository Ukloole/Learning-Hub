<?php
/**
 * UKLOOLE — Assessment Question Bank
 * The 40 multiple-choice questions used by assessment.php
 * Source: Assessment_QuestionPaper_AnswerKey.docx
 *
 * Format:
 *   id   => question number (1..40)
 *   q    => question text
 *   opts => letter => option text  (A,B,C,D)
 *   ans  => correct letter
 *   exp  => assessor explanation (used in admin review only)
 *   sec  => section label (Module 1 or Module 2)
 */

return [
    // ============================================================
    // MODULE 1 — Customer Service Foundations
    // ============================================================
    [
        'id' => 1, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'Which of the following best describes the core goal of great customer service?',
        'opts'=> [
            'A' => 'Answering calls as quickly as possible',
            'B' => 'Making people feel confident choosing your brand again',
            'C' => 'Following company scripts precisely',
            'D' => 'Resolving complaints without apologising',
        ],
        'ans' => 'B',
        'exp' => 'The core goal of customer service is making people feel confident choosing your brand again.',
    ],
    [
        'id' => 2, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'The Three Pillars of Excellent Service are:',
        'opts'=> [
            'A' => 'Speed, Accuracy, and Compliance',
            'B' => 'Empathy, Communication, and Professionalism',
            'C' => 'Patience, Politeness, and Performance',
            'D' => 'Knowledge, Confidence, and Scripting',
        ],
        'ans' => 'B',
        'exp' => 'The Three Pillars are Empathy, Communication, and Professionalism.',
    ],
    [
        'id' => 3, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "A 'Moment of Truth' in customer service refers to:",
        'opts'=> [
            'A' => 'The moment a customer receives their refund',
            'B' => "Any interaction where a customer's perception of the brand is formed",
            'C' => 'When a supervisor monitors a call',
            'D' => 'The final step in resolving a complaint',
        ],
        'ans' => 'B',
        'exp' => "A Moment of Truth is any interaction that shapes the customer's perception of the brand.",
    ],
    [
        'id' => 4, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "Agent A says: \"Okay, I'll check that.\" Agent B says: \"I'm really sorry to hear that — let me check right now so we can fix this quickly.\" What is the key difference?",
        'opts'=> [
            'A' => 'Agent B uses more words',
            'B' => 'Agent A is more professional',
            'C' => 'Agent B shows empathy and urgency, transforming a transaction into a moment of truth',
            'D' => 'There is no meaningful difference',
        ],
        'ans' => 'C',
        'exp' => 'Agent B demonstrates empathy and urgency — transforming a transaction into a moment of truth.',
    ],
    [
        'id' => 5, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'According to the Maya Angelou quote featured in the course, people will never forget:',
        'opts'=> [
            'A' => 'What you said',
            'B' => 'What you did',
            'C' => 'How you made them feel',
            'D' => 'How long you took to respond',
        ],
        'ans' => 'C',
        'exp' => 'Maya Angelou: people never forget how you made them feel.',
    ],
    [
        'id' => 6, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "When a customer says 'This is taking too long!', the underlying emotion is most likely:",
        'opts'=> [
            'A' => 'Boredom',
            'B' => 'Excitement',
            'C' => 'Feeling like no one cares about their time',
            'D' => 'Satisfaction',
        ],
        'ans' => 'C',
        'exp' => "'This is taking too long' = the customer feels no one cares about their time.",
    ],
    [
        'id' => 7, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "Which of the four customer emotional states describes someone who is 'lost, unsure of the process, or overwhelmed by information'?",
        'opts'=> [
            'A' => 'Angry',
            'B' => 'Anxious',
            'C' => 'Confused',
            'D' => 'Neutral',
        ],
        'ans' => 'C',
        'exp' => 'Confused customers are lost, unsure, or overwhelmed by information.',
    ],
    [
        'id' => 8, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'When disarming an angry customer, what is the MOST important first step?',
        'opts'=> [
            'A' => 'Immediately transfer them to a supervisor',
            'B' => "Defend the company's policies",
            'C' => 'Acknowledge their frustration and show empathy before moving to action',
            'D' => 'Ask them to calm down',
        ],
        'ans' => 'C',
        'exp' => 'Acknowledge frustration and show empathy before moving to action — never defend or interrupt.',
    ],
    [
        'id' => 9, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "For an anxious customer who fears their booking didn't go through, the best response is:",
        'opts'=> [
            'A' => 'Tell them to check their email later',
            'B' => 'Confirm their booking clearly and offer to send a confirmation email',
            'C' => 'Ask them several clarifying questions first',
            'D' => 'Put them on hold while you investigate',
        ],
        'ans' => 'B',
        'exp' => 'Confirm clearly and offer a confirmation email — provide certainty.',
    ],
    [
        'id' => 10, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'Which phrase is recommended for guiding a confused customer?',
        'opts'=> [
            'A' => "'That's not how it works.'",
            'B' => "'Let me transfer you to someone else.'",
            'C' => "'Let's go step by step. I'll walk you through it.'",
            'D' => "'You should have read the instructions.'",
        ],
        'ans' => 'C',
        'exp' => "'Let's go step by step. I'll walk you through it.' builds instant trust.",
    ],
    [
        'id' => 11, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'The Tone Triangle consists of:',
        'opts'=> [
            'A' => 'Voice, Speed, and Volume',
            'B' => 'Words, Tone, and Timing',
            'C' => 'Empathy, Clarity, and Action',
            'D' => 'Listening, Speaking, and Writing',
        ],
        'ans' => 'B',
        'exp' => 'The Tone Triangle: Words, Tone, and Timing.',
    ],
    [
        'id' => 12, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'Why does tone often matter more than words in customer service?',
        'opts'=> [
            'A' => 'Customers rarely pay attention to words',
            'B' => "Your tone conveys the emotional message — if it doesn't align, customers won't believe what you say",
            'C' => 'Words are harder to control than tone',
            'D' => "Most interactions happen over text where tone doesn't apply",
        ],
        'ans' => 'B',
        'exp' => 'Tone carries the emotional message — misaligned tone cancels out the right words.',
    ],
    [
        'id' => 13, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "What does the 'A' stand for in the A.C.E.D. Communication Formula?",
        'opts'=> [
            'A' => 'Apologise',
            'B' => 'Assess',
            'C' => 'Acknowledge',
            'D' => 'Answer',
        ],
        'ans' => 'C',
        'exp' => "A = Acknowledge — recognise the customer's situation immediately.",
    ],
    [
        'id' => 14, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "In the A.C.E.D. Formula, the 'Clarify' step is important because:",
        'opts'=> [
            'A' => 'It allows you to speak for longer',
            'B' => 'Most agents make the mistake of assuming instead of confirming details',
            'C' => 'Customers prefer to repeat themselves',
            'D' => 'It gives you time to check the system',
        ],
        'ans' => 'B',
        'exp' => 'Most agents assume instead of asking, causing them to solve the wrong problem.',
    ],
    [
        'id' => 15, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'Which of the following is an example of an empathy phrase from the A.C.E.D. Formula?',
        'opts'=> [
            'A' => "'I'll put you on hold.'",
            'B' => "'That's not my department.'",
            'C' => "'I'd feel the same way in your situation.'",
            'D' => "'Please hold while I read the policy.'",
        ],
        'ans' => 'C',
        'exp' => "'I'd feel the same way in your situation.' is a genuine empathy phrase.",
    ],
    [
        'id' => 16, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "When 'Delivering' a solution in the A.C.E.D. Formula, what three steps should you follow?",
        'opts'=> [
            'A' => 'Apologise, explain, and close',
            'B' => 'Give the solution, explain it simply, and confirm it works',
            'C' => 'Transfer, escalate, and log',
            'D' => 'Read the script, confirm, and end the call',
        ],
        'ans' => 'B',
        'exp' => 'Deliver: give the solution, explain simply, confirm it works.',
    ],
    [
        'id' => 17, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "Which of the following is identified as a 'mistake that ruins trust' in the Communication Formula lesson?",
        'opts'=> [
            'A' => 'Being too warm',
            'B' => 'Over-apologising, which sounds insecure',
            'C' => 'Speaking too slowly',
            "D" => "Using the customer's name",
        ],
        'ans' => 'B',
        'exp' => 'Over-apologising sounds insecure. Acknowledge once, then focus on the solution.',
    ],
    [
        'id' => 18, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => 'In terms of voice technique, which of the following is a recommended pro tip?',
        'opts'=> [
            'A' => 'Speak as fast as possible to show efficiency',
            'B' => 'Use a deep, serious tone at all times',
            'C' => 'Smile while talking — it translates into your voice',
            'D' => 'Avoid pausing as it sounds unprofessional',
        ],
        'ans' => 'C',
        'exp' => 'Smiling while talking genuinely translates into your vocal quality.',
    ],
    [
        'id' => 19, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "What is the main difference between a 'flat tone' and a 'warm tone' in customer service?",
        'opts'=> [
            'A' => 'Flat tone uses fewer words',
            'B' => 'Warm tone adds helpfulness, urgency and genuine engagement; flat tone sounds indifferent',
            'C' => 'Flat tone is more professional',
            'D' => 'There is no meaningful difference in outcome',
        ],
        'ans' => 'B',
        'exp' => 'Warm tone adds engagement and helpfulness; flat tone sounds indifferent and impersonal.',
    ],
    [
        'id' => 20, 'sec' => 'Module 1: Customer Service Foundations',
        'q'   => "Which of the following is listed as a 'tone killer' to avoid?",
        'opts'=> [
            'A' => 'Speaking clearly',
            'B' => 'Using empathy phrases',
            'C' => "Using filler words like 'umm' and 'okayyy'",
            'D' => 'Pausing naturally',
        ],
        'ans' => 'C',
        'exp' => "Filler words like 'umm' and 'okayyy' make you sound hesitant and insincere.",
    ],

    // ============================================================
    // MODULE 2 — Tools, Systems & Digital Professionalism
    // ============================================================
    [
        'id' => 21, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'What does CRM stand for?',
        'opts'=> [
            'A' => 'Customer Records Management',
            'B' => 'Customer Relationship Management',
            'C' => 'Case Resolution Method',
            'D' => 'Communication Response Mechanism',
        ],
        'ans' => 'B',
        'exp' => 'CRM = Customer Relationship Management.',
    ],
    [
        'id' => 22, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which of the following best describes what a CRM system does?',
        'opts'=> [
            'A' => 'Monitors agent performance only',
            'B' => 'Stores, organises, and tracks customer interactions so nothing falls through the cracks',
            'C' => 'Automatically resolves customer complaints',
            'D' => 'Sends automated emails to customers',
        ],
        'ans' => 'B',
        'exp' => 'CRM stores, organises, and tracks customer interactions — nothing falls through the cracks.',
    ],
    [
        'id' => 23, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which of the following is NOT a benefit of using a CRM for agents?',
        'opts'=> [
            'A' => 'No repeated questions — quick access to history',
            'B' => 'Clear accountability for each case',
            'C' => 'Automatically resolves all cases without agent input',
            'D' => 'Professional tracking for escalations',
        ],
        'ans' => 'C',
        'exp' => 'CRMs do not automatically resolve cases — agents still do the work.',
    ],
    [
        'id' => 24, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Popular CRM platforms include Salesforce, HubSpot, Zoho, and Zendesk. What is true about all of them?',
        'opts'=> [
            'A' => "They are completely different and skills don't transfer",
            'B' => 'They all share the same core concepts — contacts, tickets, timelines, and reports',
            'C' => 'Only Salesforce is used in professional settings',
            'D' => 'They can only be used by large corporations',
        ],
        'ans' => 'B',
        'exp' => 'All major CRM platforms share the same core concepts — skills transfer between them.',
    ],
    [
        'id' => 25, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => "Which CRM function involves using statuses like 'New', 'In Progress', 'On Hold', and 'Resolved'?",
        'opts'=> [
            'A' => 'Case creation',
            'B' => 'Customer profiles',
            'C' => 'Ticket tracking',
            'D' => 'Escalation',
        ],
        'ans' => 'C',
        'exp' => 'Ticket tracking uses status labels to show where a case stands.',
    ],
    [
        'id' => 26, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'A good CRM note should include which of the following?',
        'opts'=> [
            'A' => "The agent's personal opinion of the customer",
            'B' => 'Case ID, action taken, timeframe, and next steps',
            'C' => "Only the customer's name and the date",
            'D' => 'A summary in informal language',
        ],
        'ans' => 'B',
        'exp' => 'A good note includes case ID, action taken, timeframe, and next steps.',
    ],
    [
        'id' => 27, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which of the following is an example of a BAD CRM note?',
        'opts'=> [
            'A' => 'Customer called re: delayed refund for booking #A321. Escalated to finance (INC-4792). Follow-up in 72hrs.',
            'B' => 'Customer called. Issue fixed.',
            'C' => 'Customer confirmed receipt of replacement order. Case closed 14:30. No further action required.',
            'D' => 'Customer reported incorrect charge on invoice #887. Referred to billing team. Resolution expected within 24hrs.',
        ],
        'ans' => 'B',
        'exp' => "'Customer called. Issue fixed.' lacks all context — case ID, action, timeframe, follow-up.",
    ],
    [
        'id' => 28, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'What does SLA stand for?',
        'opts'=> [
            'A' => 'Standard Logging Activity',
            'B' => 'Service Level Agreement',
            'C' => 'System Limit Assessment',
            'D' => 'Support Log Archive',
        ],
        'ans' => 'B',
        'exp' => 'SLA = Service Level Agreement.',
    ],
    [
        'id' => 29, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which SLA metric measures how fast a problem is fully resolved?',
        'opts'=> [
            'A' => 'Response time',
            'B' => 'Escalation time',
            'C' => 'Resolution time',
            'D' => 'Acknowledgement time',
        ],
        'ans' => 'C',
        'exp' => 'Resolution time measures how fast the problem is fully fixed.',
    ],
    [
        'id' => 30, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => "A ticket marked 'Urgent' or 'Critical' typically means:",
        'opts'=> [
            'A' => 'A general question that can wait',
            'B' => 'The system is down, money is at risk, or many people are affected',
            'C' => 'The customer requested a callback',
            'D' => 'The agent is unavailable',
        ],
        'ans' => 'B',
        'exp' => 'Urgent/Critical = system down, money at risk, or many people affected.',
    ],
    [
        'id' => 31, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which of the following is a recommended habit for managing time within SLAs?',
        'opts'=> [
            'A' => 'Wait until the SLA deadline has passed before escalating',
            'B' => 'Sort tickets by urgency and address the most time-sensitive cases first',
            'C' => 'Handle tickets in the order they were received regardless of priority',
            'D' => 'Only check SLA timers at the end of a shift',
        ],
        'ans' => 'B',
        'exp' => 'Sort by urgency — address the most time-sensitive cases first.',
    ],
    [
        'id' => 32, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Why is it important to send a short update to a customer BEFORE an SLA deadline is breached?',
        'opts'=> [
            'A' => 'It closes the ticket automatically',
            'B' => 'It demonstrates proactive communication and reduces customer frustration',
            'C' => 'It resets the SLA timer',
            'D' => 'It transfers responsibility to the customer',
        ],
        'ans' => 'B',
        'exp' => 'Proactive updates reduce frustration and demonstrate professionalism.',
    ],
    [
        'id' => 33, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => "In professional digital communication, what does 'using threads' mean?",
        'opts'=> [
            'A' => 'Sending multiple separate messages on the same topic',
            'B' => 'Replying within an existing conversation chain to keep topics organised',
            'C' => 'Tagging every team member in a message',
            'D' => 'Using coloured text to highlight important points',
        ],
        'ans' => 'B',
        'exp' => 'Using threads = replying within an existing conversation chain to keep things organised.',
    ],
    [
        'id' => 34, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which of the following is the correct four-part framework for writing a concise internal update?',
        'opts'=> [
            'A' => 'Greeting, Problem, Solution, Closing',
            'B' => 'Context, Action Taken, Next Step, Deadline',
            'C' => 'Who, What, When, Apology',
            'D' => 'Summary, Escalation, Timeline, Signature',
        ],
        'ans' => 'B',
        'exp' => 'The four-part framework: Context, Action Taken, Next Step, Deadline.',
    ],
    [
        'id' => 35, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'When collaborating with other departments, which behaviour should be avoided?',
        'opts'=> [
            'A' => 'Providing a clear case summary',
            'B' => "Explaining your department's perspective",
            'C' => 'Shifting blame when challenges arise',
            'D' => 'Respecting different team priorities',
        ],
        'ans' => 'C',
        'exp' => 'Shifting blame is damaging to cross-team relationships and slows resolution.',
    ],
    [
        'id' => 36, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'According to the Queue Discipline lesson, why should you avoid constant task switching between cases?',
        'opts'=> [
            'A' => 'It confuses the customer',
            'B' => 'It reduces accuracy and increases total resolution time',
            'C' => 'It violates company policy',
            'D' => 'It uses up more system resources',
        ],
        'ans' => 'B',
        'exp' => 'Constant task switching reduces accuracy and increases total resolution time.',
    ],
    [
        'id' => 37, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'When escalating a case, which of the following MUST be included?',
        'opts'=> [
            'A' => "Only the customer's phone number",
            'B' => 'Case summary, steps already taken, evidence, and a clear request',
            'C' => 'A verbal explanation only — no documentation needed',
            'D' => "The agent's personal recommendation about the customer",
        ],
        'ans' => 'B',
        'exp' => 'Escalation must include: case summary, steps taken, evidence, and a clear request.',
    ],
    [
        'id' => 38, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'What is the key difference between internal notes and external notes?',
        'opts'=> [
            'A' => 'Internal notes are longer',
            'B' => 'External notes use technical language; internal notes use plain English',
            'C' => 'Internal notes can be technical; external notes must be customer-friendly and empathetic',
            'D' => 'There is no difference — both should look the same',
        ],
        'ans' => 'C',
        'exp' => 'Internal = technical/team language; external = customer-friendly and empathetic.',
    ],
    [
        'id' => 39, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'A proper handover to a colleague should include which four elements?',
        'opts'=> [
            'A' => 'Agent name, shift time, phone number, and case summary',
            'B' => 'Case ID, current status, next step, and risks',
            'C' => 'Customer name, complaint, resolution, and signature',
            'D' => 'Ticket number, priority, department, and date opened',
        ],
        'ans' => 'B',
        'exp' => 'Proper handover: Case ID, current status, next step, and risks.',
    ],
    [
        'id' => 40, 'sec' => 'Module 2: Tools, Systems & Digital Professionalism',
        'q'   => 'Which statement about organised agents is TRUE according to the Case & Workflow Management lesson?',
        'opts'=> [
            'A' => 'Organisation slows down response times',
            'B' => 'Organised agents are less likely to be promoted',
            'C' => 'Consistent workflow management builds the foundation for career advancement',
            'D' => 'Organisation only matters for senior agents',
        ],
        'ans' => 'C',
        'exp' => 'Consistent workflow management builds the foundation for career advancement.',
    ],
];
