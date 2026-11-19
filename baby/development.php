<?php
// =============================================================================
// Momora AI - Baby Development Milestones Endpoint
// =============================================================================

require_once __DIR__ . '/../config/config.php';

function clean_dots($value) {
    if (is_string($value)) {
        return preg_replace('/\s*\?\?\s*/u', ' · ', $value);
    }
    if (is_array($value)) {
        $result = [];
        foreach ($value as $k => $v) {
            $result[$k] = clean_dots($v);
        }
        return $result;
    }
    return $value;
}

$input = getInput();
$week = filter_var($input['week'] ?? 24, FILTER_VALIDATE_INT);
if (!$week || $week < 1) $week = 24;

// Weekly milestones catalogue for weeks 1 through 40
$milestones = [
    1 => [
        [
            'title' => 'Conception & Journey',
            'subtitle' => 'Fertilized egg begins dividing into specialized cells.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Following fertilization, the single-cell zygote begins a remarkable process of rapid cellular division. As it multiplies into a hollow ball of cells known as a blastocyst, it travels gently down the fallopian tube. This crucial passage sets the foundation for the entire pregnancy journey ahead.',
            'tip' => 'Focus on staying hydrated and continuing your daily prenatal vitamins containing folic acid.'
        ],
        [
            'title' => 'Early Cellular Division',
            'subtitle' => 'The blastocyst journeys down the fallopian tube.',
            'iconType' => 'SOUND',
            'detail' => 'The rapidly multiplying cells are already organizing into distinct groups that will form the embryo and the placenta. High levels of natural progesterone support this early environment by relaxing surrounding tissues. Your body is quietly preparing for the momentous process of implantation in the coming days.',
            'tip' => 'Prioritize balanced meals with plenty of fresh produce and whole grains to support cellular growth.'
        ],
    ],
    2 => [
        [
            'title' => 'Uterine Implantation',
            'subtitle' => 'Blastocyst burrows into the nutrient-rich uterine wall.',
            'iconType' => 'MOVEMENT',
            'detail' => 'The blastocyst reaches the uterus and begins burrowing into the thickened, nutrient-dense endometrial lining. Specialized outer cells create microscopic projections that anchor the cluster securely in place. This delicate attachment establishes the primary connection between mother and developing embryo.',
            'tip' => 'Give yourself permission to rest and maintain a calm, low-stress routine.'
        ],
        [
            'title' => 'Amniotic Sac Forms',
            'subtitle' => 'Early cushioning fluid surrounds the developing cluster.',
            'iconType' => 'SOUND',
            'detail' => 'A tiny fluid-filled space forms around the embryo, which will soon expand into the protective amniotic sac. This fluid acts as a gentle shock absorber and provides constant temperature regulation for the developing cells. At the same time, the yolk sac develops to nourish the embryo before the placenta takes over.',
            'tip' => 'Drink plenty of water throughout the day to support early fluid volume expansion.'
        ],
    ],
    3 => [
        [
            'title' => 'Germ Layer Formation',
            'subtitle' => 'Ectoderm, mesoderm, and endoderm begin differentiating.',
            'iconType' => 'MOVEMENT',
            'detail' => 'The embryonic disc organizes into three specialized germ layers that will form all body tissues. The ectoderm will produce the skin and nervous system, while the mesoderm builds bones, muscles, and blood vessels. Meanwhile, the inner endoderm develops into the respiratory tract and digestive organs.',
            'tip' => 'Incorporate foods rich in iron and protein to help support tissue differentiation.'
        ],
        [
            'title' => 'Primitive Streak',
            'subtitle' => 'The blueprint for baby’s spinal axis begins to take shape.',
            'iconType' => 'SOUND',
            'detail' => 'A delicate groove called the primitive streak appears along the center of the embryo, defining its head-to-tail axis. This critical structure establishes bilateral symmetry and guides the precise migration of developing cells. It marks the very earliest architectural plan of your baby’s future backbone and spine.',
            'tip' => 'Ensure you are getting adequate rest as early metabolic changes begin taking place.'
        ],
    ],
    4 => [
        [
            'title' => 'Early Neural Tube',
            'subtitle' => 'The foundation of the brain and spinal cord starts closing.',
            'iconType' => 'SOUND',
            'detail' => 'A specialized ribbon of tissue folds inward along the embryo’s back to form the neural tube. This vital structure will rapidly develop into the brain at one end and the spinal cord along the length of the back. Proper closure of the tube is one of the most significant early developmental milestones.',
            'tip' => 'Continue taking daily folic acid, which is essential during this phase of neural tube closure.'
        ],
        [
            'title' => 'Tiny Heart Tube',
            'subtitle' => 'A microscopic vascular channel begins early contractions.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Two microscopic vascular channels fuse together to form a primitive, single-chambered heart tube. Delicate cardiac muscle fibers begin rhythmic contractions, circulating primitive fluid through microscopic channels. This marks the earliest beginning of your baby’s cardiovascular circulation system.',
            'tip' => 'Incorporate gentle walks into your daily schedule to support your own healthy circulation.'
        ],
    ],
    5 => [
        [
            'title' => 'First Heartbeat',
            'subtitle' => 'The tiny heart begins beating at a steady rhythm.',
            'iconType' => 'SOUND',
            'detail' => 'The tiny embryonic heart begins pulsing at a steady, rapid pace that can often be observed on early ultrasound scans. It is currently the size of a tiny sesame seed, yet it is actively pumping blood throughout the microscopic vascular network. Heart valves and chambers are continuing to partition into their final four-chamber structure.',
            'tip' => 'Keep nutritious, easy-to-digest snacks on hand in case early morning sickness begins.'
        ],
        [
            'title' => 'Early Limb Buds',
            'subtitle' => 'Paddle-like buds begin emerging for arms and legs.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Tiny paddle-shaped tissue swellings emerge along the embryo’s torso, signaling the beginning of arm and leg formation. The upper limb buds appear first, followed closely by the lower buds just a couple of days later. Beneath the surface, microscopic nerve fibers are already extending into these developing extremities.',
            'tip' => 'Stay comfortable and wear breathable clothing as your body temperature naturally rises.'
        ],
    ],
    6 => [
        [
            'title' => 'Facial Features Forming',
            'subtitle' => 'Nostrils, eye dimples, and ear pits start to form.',
            'iconType' => 'SOUND',
            'detail' => 'Dark spots mark where the baby’s retinas and eye lenses are forming, while shallow depressions indicate the nostrils and ears. The jawline, mouth opening, and tiny tongue tissues are gradually emerging from primitive arches. These rapid structural changes will give the embryo an increasingly recognizable facial profile.',
            'tip' => 'Sip ginger tea or lemon-infused water if you experience waves of pregnancy nausea.'
        ],
        [
            'title' => 'Brain Hemispheres',
            'subtitle' => 'Brain vesicles swell and divide into distinct regions.',
            'iconType' => 'MOVEMENT',
            'detail' => 'The anterior end of the neural tube expands into three primary brain vesicles that further divide into five distinct cerebral zones. These specialized brain regions will control memory, movement, sensory processing, and vital autonomic functions. Neural connections are forming at an astonishing rate of hundreds of thousands per minute.',
            'tip' => 'Incorporate DHA and omega-3 fatty acids into your nutrition to support early brain development.'
        ],
    ],
    7 => [
        [
            'title' => 'Spontaneous Twitches',
            'subtitle' => 'Microscopic reflexive movements begin in the torso.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Early nerve pathways connect with developing torso muscle fibers, producing tiny involuntary twitches. Although these subtle flexes are far too miniature to be felt, they are crucial for strengthening emerging muscle tissues. The embryo’s skeleton is still composed of soft, translucent cartilage that flexes easily.',
            'tip' => 'Practice deep breathing exercises to help alleviate fatigue and maintain steady energy levels.'
        ],
        [
            'title' => 'Retinal Pigment',
            'subtitle' => 'Dark pigment develops in the embryonic eye cups.',
            'iconType' => 'SOUND',
            'detail' => 'The optic cups develop rich layers of melanin pigment that clearly define where your baby’s eyes will be. The eyelids begin forming as delicate folds of skin, although they will remain temporarily fused shut to protect internal eye structures. The lens and optic nerve pathways are continuing to connect back toward the brain.',
            'tip' => 'Protect your own eyes from screen fatigue and ensure you get sufficient sleep each night.'
        ],
    ],
    8 => [
        [
            'title' => 'Fingers & Toes Unwebbing',
            'subtitle' => 'Distinct digits appear as paddle webbing recedes.',
            'iconType' => 'MOVEMENT',
            'detail' => 'The paddle-shaped limb buds develop small digital rays where tiny fingers and toes will soon separate. Programmed cellular changes cause the delicate webbing between digits to gradually dissolve, freeing individual fingers. Tiny elbows and knees can now bend as rudimentary joints begin to articulate.',
            'tip' => 'Ensure your daily diet includes adequate calcium to support baby’s cartilage and joint formation.'
        ],
        [
            'title' => 'Inner Ear Architecture',
            'subtitle' => 'Balance and hearing organs take their early shape.',
            'iconType' => 'SOUND',
            'detail' => 'The delicate semicircular canals of the inner ear, which govern balance and spatial orientation, are carving their pathways. The cochlea, responsible for future hearing and sound frequency detection, is coiling into its classic spiral structure. Although sound processing comes later, the physical groundwork is advancing rapidly.',
            'tip' => 'Listen to calming music or nature sounds during relaxation breaks to help reduce daily stress.'
        ],
    ],
    9 => [
        [
            'title' => 'Fetal Stage Begins',
            'subtitle' => 'Essential organs are in place and begin functional maturation.',
            'iconType' => 'SOUND',
            'detail' => 'Having completed the embryonic period, your baby is now officially referred to as a fetus. All fundamental organ systems have formed their basic structures and will now spend the remaining months growing and maturing. The tiny heart has now divided into distinct ventricles and atria, beating vigorously.',
            'tip' => 'Schedule your routine prenatal checkups and keep a list of questions ready for your care team.'
        ],
        [
            'title' => 'Bending Joints',
            'subtitle' => 'Tiny elbows, wrists, and knees can flex smoothly.',
            'iconType' => 'MOVEMENT',
            'detail' => 'The fetus can now bring its tiny hands together across the chest and bend both elbows and wrists. Although you will not feel these subtle motions for several more weeks, baby is actively rehearsing basic motor coordination. Muscles are growing thicker and responding to initial electrical impulses from the spinal cord.',
            'tip' => 'Stay active with mild, physician-approved stretching to support your flexibility.'
        ],
    ],
    10 => [
        [
            'title' => 'Tiny Fingernails',
            'subtitle' => 'Delicate nail beds form on baby’s miniature fingertips.',
            'iconType' => 'SOUND',
            'detail' => 'Microscopic nail beds begin forming on the tips of all ten fingers and toes as delicate skin layers specialize. The skin itself remains extremely thin and translucent, revealing the intricate network of blood vessels beneath. Tiny hair follicles are also starting to develop beneath the epidermal surface.',
            'tip' => 'Keep your skin hydrated and moisturized as your body accommodates gentle early changes.'
        ],
        [
            'title' => 'Swallowing Reflex',
            'subtitle' => 'Baby practices swallowing tiny droplets of fluid.',
            'iconType' => 'MOVEMENT',
            'detail' => 'The fetus begins opening and closing its mouth, swallowing tiny sips of amniotic fluid to practice digestive reflexes. This fluid passes through the developing stomach and kidneys, which are already beginning to produce dilute urine. This natural cycle contributes directly to maintaining the volume and balance of the amniotic environment.',
            'tip' => 'Drink water consistently throughout the day to support healthy amniotic fluid production.'
        ],
    ],
    11 => [
        [
            'title' => 'Fist Clenching',
            'subtitle' => 'Fingers can open, close, and clench into gentle fists.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s hands have become much more functional, allowing the tiny fingers to curl inward and make gentle fist-like grips. As motor reflexes sharpen, the fetus can stretch, kick, and even practice sucking on its miniature thumbs. These coordinated movements stimulate muscle growth and expand brain-body pathways.',
            'tip' => 'Include magnesium-rich foods like nuts, seeds, and leafy greens to prevent muscle cramps.'
        ],
        [
            'title' => 'Tooth Buds Under Gums',
            'subtitle' => 'Twenty primary tooth buds are nestled in place.',
            'iconType' => 'SOUND',
            'detail' => 'Deep within the upper and lower jawbones, all twenty primary baby tooth buds have quietly aligned in their designated places. These miniature dental structures will remain safely buried beneath the gums until months after birth. The palate has also fused completely, separating the nasal cavity from the oral passage.',
            'tip' => 'Maintain thorough dental hygiene and schedule regular dental cleanings during pregnancy.'
        ],
    ],
    12 => [
        [
            'title' => 'First Trimester Completion',
            'subtitle' => 'Major anatomical systems have finished initial formation.',
            'iconType' => 'SOUND',
            'detail' => 'Reaching the end of the first trimester marks a pivotal milestone as critical structural development is largely complete. The placenta is now fully formed and efficiently taking over primary hormone and nutrient exchange. For many expectant mothers, early pregnancy fatigue and nausea begin to steadily improve.',
            'tip' => 'Celebrate reaching this milestone and discuss your second trimester routine with your provider.'
        ],
        [
            'title' => 'Reflexive Curls',
            'subtitle' => 'Toes and fingers curl when stimulated reflexively.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s central nervous system can now transmit basic reflex signals from sensory receptors straight to peripheral limbs. If the abdominal wall is gently pressed, the fetus will often shift or curl its toes in response. The vocal cords are also completing their initial development within the delicate larynx.',
            'tip' => 'Consider taking a gentle walk or doing light yoga to celebrate your renewed physical energy.'
        ],
    ],
    13 => [
        [
            'title' => 'Unique Fingerprints',
            'subtitle' => 'One-of-a-kind ridge patterns set permanently on fingertips.',
            'iconType' => 'SOUND',
            'detail' => 'Intricate friction ridges form on your baby’s fingertips and palms, creating their entirely unique, permanent fingerprints. These swirls and loops will remain unchanged throughout their entire lifetime as a defining personal signature. Vocal cord development is also advancing, preparing the vocal apparatus for post-birth cries.',
            'tip' => 'Stay mindful of proper posture as your center of gravity begins its subtle forward shift.'
        ],
        [
            'title' => 'Active Somersaults',
            'subtitle' => 'Baby glides and turns gracefully in the amniotic pool.',
            'iconType' => 'MOVEMENT',
            'detail' => 'With ample room inside the amniotic sac, your baby can somersault, kick, and stretch with complete freedom. The amniotic fluid provides weightless buoyancy that allows smooth, unrestricted skeletal and muscle exercise. These buoyant exercises help develop neuromuscular coordination long before birth.',
            'tip' => 'Wear supportive, comfortable footwear to keep your feet and joints cushioned throughout the day.'
        ],
    ],
    14 => [
        [
            'title' => 'Facial Expressions',
            'subtitle' => 'Baby practices grimacing, squinting, and frowning.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Facial nerve development allows your baby to squint, frown, grimace, and produce various miniature expressions. These movements are reflexive exercises that strengthen the delicate muscles of facial articulation. Baby’s neck has also lengthened, lifting the chin off the chest for a more upright posture.',
            'tip' => 'Incorporate vitamin C into your meals to support collagen synthesis and skin elasticity.'
        ],
        [
            'title' => 'Fine Lanugo Hair',
            'subtitle' => 'A soft downy coat covers skin for protection and warmth.',
            'iconType' => 'SOUND',
            'detail' => 'An ultra-fine, downy coating of hair called lanugo begins sprouting across baby’s shoulders, back, and forehead. This soft blanket helps hold the protective vernix coating against delicate skin while immersed in fluid. Most of this silky lanugo will naturally shed before full term arrives.',
            'tip' => 'Keep a soothing evening routine to help you unwind and achieve restful sleep.'
        ],
    ],
    15 => [
        [
            'title' => 'Light Perception',
            'subtitle' => 'Baby can sense bright light shining through uterine walls.',
            'iconType' => 'SOUND',
            'detail' => 'Although baby’s eyelids remain closed, the retinas are sensitive enough to perceive bright light cast on your abdomen. If an external light source shines toward your belly, the baby may gently turn away from the illumination. Delicate ear structures continue to migrate toward their final anatomical positions on the sides of the head.',
            'tip' => 'Spend time outdoors in natural daylight to support your circadian rhythm and mood.'
        ],
        [
            'title' => 'Brisk Kicks & Turns',
            'subtitle' => 'Legs and arms stretch out with increasing vigor.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s legs are growing longer than their arms, allowing for more energetic kicking against the uterine wall. The tiny skeleton is gradually accumulating calcium, hardening soft cartilage into firmer, more resilient bone. You might begin feeling very subtle flutter sensations in the coming weeks.',
            'tip' => 'Ensure your daily diet contains adequate dietary calcium and vitamin D for bone strength.'
        ],
    ],
    16 => [
        [
            'title' => 'Hearing Middle Ear',
            'subtitle' => 'Ossicles in the ear begin detecting muffled vibrations.',
            'iconType' => 'SOUND',
            'detail' => 'The tiny middle ear bones called the malleus, incus, and stapes are hardening and beginning to conduct sound vibrations. Your baby can now perceive muffled sounds from your body, including the beat of your heart and the whoosh of blood circulation. External loud noises may also begin making an impression on the auditory pathways.',
            'tip' => 'Speak in warm, conversational tones; your baby is gradually becoming attuned to your voice.'
        ],
        [
            'title' => 'Heart Pumping Power',
            'subtitle' => 'Circulatory system circulates up to 25 quarts daily.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s cardiovascular system has strengthened considerably, pumping up to twenty-five quarts of blood each single day. The heart beats at approximately 140 to 150 beats per minute, which is roughly twice the rate of an adult pulse. This robust circulation delivers vital oxygen and nutrients to every rapidly expanding organ.',
            'tip' => 'Stay well-hydrated to help your own circulating blood volume maintain its steady expansion.'
        ],
    ],
    17 => [
        [
            'title' => 'Cartilage to Bone',
            'subtitle' => 'Soft skeletal cartilage undergoes rapid ossification.',
            'iconType' => 'SOUND',
            'detail' => 'The soft, flexible cartilage that composed your baby’s early skeleton is undergoing a rapid ossification process into true bone. The umbilical cord is also thickening and developing a firm, jelly-like coating to protect blood vessels from compression. This structural reinforcement ensures uninterrupted nutrient transfer as baby moves actively.',
            'tip' => 'Choose whole grains, dairy, or fortified plant milks to provide essential skeletal building blocks.'
        ],
        [
            'title' => 'Adipose Storage',
            'subtitle' => 'Baby accumulates protective brown fat layers.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Specialized brown adipose tissue begins accumulating beneath baby’s skin, particularly around the neck, chest, and back. This protective fat layer plays a crucial role in post-birth body heat production and thermal regulation. Sweat glands are also beginning to take shape across the skin surface.',
            'tip' => 'Incorporate healthy fats such as avocados, olive oil, and nuts into your balanced meals.'
        ],
    ],
    18 => [
        [
            'title' => 'First Felt Flutters',
            'subtitle' => 'Gentle butterfly taps known as quickening begin.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Many mothers feel the first delicate movements, often described as gentle butterflies, bubbles, or light tapping. Known clinically as quickening, these early flutters confirm your baby’s growing physical strength and coordination. Over the next month, these subtle sensations will evolve into distinct, recognizable kicks.',
            'tip' => 'Take time in the quiet evening to sit comfortably with your hands on your abdomen and feel for flutters.'
        ],
        [
            'title' => 'Recognizing Familiar Voices',
            'subtitle' => 'Baby tunes in to the pitch and cadence of your voice.',
            'iconType' => 'SOUND',
            'detail' => 'Because sound travels clearly through amniotic fluid, your voice is becoming the most familiar sound in baby’s world. The auditory nerve fibers are forming permanent synapses with the temporal lobe of the developing brain. Baby learns to associate the rhythm of your cadence with feelings of safety and comfort.',
            'tip' => 'Sing, hum, or read your favorite books aloud to strengthen your early auditory connection.'
        ],
    ],
    19 => [
        [
            'title' => 'Vernix Protection',
            'subtitle' => 'A creamy barrier coats the skin against moisture exposure.',
            'iconType' => 'SOUND',
            'detail' => 'A thick, creamy white substance called vernix caseosa begins coating baby’s delicate skin surface. Produced by specialized sebaceous glands and mixed with shed cells, it acts as a waterproof protective barrier against amniotic fluid. It also possesses natural antimicrobial properties that protect delicate skin throughout pregnancy.',
            'tip' => 'Use gentle, fragrance-free lotions on your own skin to soothe any tightness or dryness.'
        ],
        [
            'title' => 'Sensory Specialization',
            'subtitle' => 'Brain sections for touch, hearing, and taste mature.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Specific sensory zones in the cerebral cortex are organizing to process distinct inputs for touch, taste, smell, and hearing. Baby’s arms and legs are in balanced proportion now, allowing for precise grasping of feet and the umbilical cord. Neural insulation called myelin begins wrapping around nerve fibers to accelerate impulse transmission.',
            'tip' => 'Maintain varied, nutrient-dense meals to expose your baby to subtle flavor nuances in the fluid.'
        ],
    ],
    20 => [
        [
            'title' => 'Halfway Milestone',
            'subtitle' => 'You have reached the midpoint of your pregnancy journey.',
            'iconType' => 'SOUND',
            'detail' => 'Week 20 represents the official halfway mark of a typical full-term pregnancy journey. The mid-pregnancy anatomy ultrasound typically occurs around this time to examine baby’s organs, limbs, and overall growth. Your uterus has now expanded to reach roughly the level of your belly button.',
            'tip' => 'Celebrate your halfway milestone and discuss your mid-pregnancy ultrasound results with your provider.'
        ],
        [
            'title' => 'Regular Sleep Cycles',
            'subtitle' => 'Baby settles into predictable resting and active rhythms.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Your baby is developing distinct sleep-and-wake rhythms, often sleeping for twenty to forty minutes at a stretch. You may notice baby stirring enthusiastically when you lie down to rest, as daytime walking gently rocks them to sleep. These defined cycles reflect increasing maturity in the central nervous system.',
            'tip' => 'Establish a relaxing bedtime routine to help your body unwind when baby is in an active phase.'
        ],
    ],
    21 => [
        [
            'title' => 'Tasting Amniotic Fluid',
            'subtitle' => 'Taste buds register subtle nuances from your meals.',
            'iconType' => 'SOUND',
            'detail' => 'Thousands of functional taste buds have developed on your baby’s tongue and throughout the lining of the mouth. Because aromatic flavor compounds pass into amniotic fluid, baby experiences early hints of the foods you eat. This early sensory exposure helps cultivate taste preferences that can carry over into infancy.',
            'tip' => 'Enjoy a rich variety of colorful vegetables and spices to introduce natural flavors to baby.'
        ],
        [
            'title' => 'Coordinated Kicking',
            'subtitle' => 'Stronger limb extensions create visible little nudges.',
            'iconType' => 'MOVEMENT',
            'detail' => 'With rapidly strengthening muscles and joints, baby’s kicks and punches are becoming more purposeful and pronounced. You may even begin noticing small ripples or brief outward nudges visible across the surface of your abdomen. Partners and family members can often feel these delightful taps by placing a flat palm on your belly.',
            'tip' => 'Invite your partner or loved one to place a warm hand on your abdomen during active moments.'
        ],
    ],
    22 => [
        [
            'title' => 'Grip & Grasping',
            'subtitle' => 'Baby reaches out and firmly grasps the umbilical cord.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s grasp reflex is remarkably well-developed, allowing them to firmly hold on to their feet or the umbilical cord. Touch receptors are densely concentrated across the hands, lips, and feet, encouraging exploratory sensations. The brain and nerve endings are communicating continuously as sensory curiosity expands.',
            'tip' => 'Engage in gentle abdominal touch or light massage to foster soothing sensory awareness.'
        ],
        [
            'title' => 'Eyebrow Contours',
            'subtitle' => 'Delicate eyebrows and eyelashes are clearly defined.',
            'iconType' => 'SOUND',
            'detail' => 'Delicate white eyebrows and fine eyelashes are distinctly visible, framing baby’s closed eyelids. Beneath the closed lids, the eyes have completed their basic anatomical architecture and can make slow tracking movements. The skin is gradually losing its transparency as melanin and subcutaneous fat accumulate.',
            'tip' => 'Ensure your prenatal supplement contains adequate vitamin A from safe beta-carotene sources.'
        ],
    ],
    23 => [
        [
            'title' => 'Startle Reaction',
            'subtitle' => 'Sudden loud sounds can prompt a brief startle jump.',
            'iconType' => 'SOUND',
            'detail' => 'Because baby’s hearing is significantly more sensitive, a sudden loud clang or barking dog may trigger a noticeable startle reflex. The baby might jump or kick briefly before settling back into a calm, relaxed posture. This responsive reflex shows that auditory pathways and motor responses are functioning in harmony.',
            'tip' => 'Reassure yourself that everyday household noises are safe and help baby become accustomed to the outside world.'
        ],
        [
            'title' => 'Rapid Eye Movement',
            'subtitle' => 'Baby enters REM dream cycles during deep sleep.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Brain wave studies reveal that fetuses at this gestational age experience active REM (rapid eye movement) sleep cycles. During these REM periods, the eyes dart gently beneath the lids while baby’s brain processes developmental memories. This active phase is considered essential for neural pathway consolidation and brain development.',
            'tip' => 'Prioritize a supportive sleep setup with comfortable pillows between your knees and under your belly.'
        ],
    ],
    24 => [
        [
            'title' => 'A World of Sound',
            'subtitle' => 'Hearing continues to develop this week.',
            'iconType' => 'SOUND',
            'detail' => 'Baby’s inner ear bones have hardened enough to conduct vibrations directly to the auditory nerve. They can now clearly perceive the rhythm of your heartbeat, maternal blood flow, and external voices. Talking, humming, or reading gently can help baby become familiar with soothing sounds in their environment.',
            'tip' => 'Take a quiet moment each day to speak or sing softly to your baby to build early bonding.'
        ],
        [
            'title' => 'Little Movements',
            'subtitle' => 'You may notice your baby\'s own rhythm.',
            'iconType' => 'MOVEMENT',
            'detail' => 'At this gestational age, your baby has developed more muscle coordination and distinct active periods. You might experience gentle taps, rolls, or even rhythmic twitches that indicate harmless fetal hiccups. Over time, you will start recognizing your baby’s unique pattern of daily activity and resting.',
            'tip' => 'Note the times of day your baby tends to be most active as their sleep-wake pattern emerges.'
        ],
    ],
    25 => [
        [
            'title' => 'Spinal Column Support',
            'subtitle' => 'Vertebrae and spinal joints harden to support posture.',
            'iconType' => 'SOUND',
            'detail' => 'The spinal column is developing rapidly, with vertebrae, joints, and ligaments strengthening to support baby’s growing frame. Capillary blood vessels are proliferating beneath the skin, giving baby a healthy pinkish complexion. The nostrils are also beginning to open up, allowing baby to practice early breathing motions.',
            'tip' => 'Maintain good lumbar posture and use a supportive cushion when sitting for extended periods.'
        ],
        [
            'title' => 'Pink Healthy Complexion',
            'subtitle' => 'Capillaries fill under skin creating a rosy tone.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Microscopic blood vessels called capillaries are filling with blood, transforming previously translucent skin into an opaque, rosy tone. Subcutaneous fat deposits continue to pad the cheeks, limbs, and torso for thermal insulation. Baby’s movements are becoming more forceful as limb musculature gains substantial mass.',
            'tip' => 'Continue eating iron-rich foods paired with vitamin C to support healthy blood volume and capillary health.'
        ],
    ],
    26 => [
        [
            'title' => 'Eyelids Parting',
            'subtitle' => 'Baby can open eyes and practice blinking smoothly.',
            'iconType' => 'SOUND',
            'detail' => 'After remaining fused shut for months, baby’s eyelids are finally parting and opening within the amniotic pool. Baby can now blink reflexively and track subtle shifts of light through the maternal abdominal wall. The iris develops early pigment, though permanent eye color may take months after birth to establish.',
            'tip' => 'Dim harsh evening lights in your home to signal winding down and promote melatonin release.'
        ],
        [
            'title' => 'Breathing Rehearsals',
            'subtitle' => 'Lungs rhythmically expand to strengthen the diaphragm.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Your baby’s lungs are practicing rhythmic breathing motions by expanding and compressing amniotic fluid in the airways. While oxygen is still provided entirely through the placenta, these rehearsals strengthen diaphragm muscles. Blood vessels within the lungs are multiplying rapidly to prepare for future gas exchange.',
            'tip' => 'Practice calm, slow belly breathing to expand your lung capacity and relieve ribcage pressure.'
        ],
    ],
    27 => [
        [
            'title' => 'Third Trimester Welcome',
            'subtitle' => 'Baby enters the home stretch with rapid brain expansion.',
            'iconType' => 'SOUND',
            'detail' => 'Entering the third trimester marks the home stretch of pregnancy, characterized by dramatic brain and weight growth. The cerebral cortex is developing sophisticated neural networks that will govern sensory and cognitive processing. Baby can now recognize the cadence and inflections of familiar family voices.',
            'tip' => 'Review your third trimester appointment calendar and discuss birth preferences with your care team.'
        ],
        [
            'title' => 'Fetal Hiccups',
            'subtitle' => 'Tiny rhythmic contractions signal harmless lung practice.',
            'iconType' => 'MOVEMENT',
            'detail' => 'You might feel repetitive, rhythmic little tapping sensations that continue for several minutes at a time. These are fetal hiccups, caused by gentle diaphragm twitches as baby practices breathing and swallowing fluid. They are completely normal, harmless, and signify healthy nervous system maturation.',
            'tip' => 'Relax when you notice hiccups; they are a normal and comforting sign of diaphragm development.'
        ],
    ],
    28 => [
        [
            'title' => 'REM Dream Waves',
            'subtitle' => 'Active brain waves suggest vivid intrauterine dreams.',
            'iconType' => 'SOUND',
            'detail' => 'Electroencephalogram studies demonstrate that baby’s brain displays clear cycles of REM sleep and distinct dream waves. The surface of the brain, previously smooth, is developing characteristic indentations and grooves to expand surface area. These sophisticated brain cycles mirror the sleep patterns observed in full-term newborns.',
            'tip' => 'Prioritize restorative nighttime sleep and consider a brief afternoon rest when feeling fatigued.'
        ],
        [
            'title' => 'Turning Toward Light',
            'subtitle' => 'Baby orients face toward bright warm illumination.',
            'iconType' => 'MOVEMENT',
            'detail' => 'With fully opened eyes and mature visual pathways, your baby will often turn their head toward warm light shining on your belly. Visual tracking and pupillary light reflexes are beginning to function smoothly. Space inside the uterus is becoming cozier, turning loose somersaults into firm stretches.',
            'tip' => 'Take time to connect with your baby by resting your hands on your abdomen in bright natural daylight.'
        ],
    ],
    29 => [
        [
            'title' => 'Calcium Absorption',
            'subtitle' => 'Massive calcium uptake strengthens developing bones.',
            'iconType' => 'SOUND',
            'detail' => 'Your baby is absorbing substantial amounts of maternal calcium each day to harden and strengthen developing bones. The skeleton is ossifying rapidly, requiring a steady supply of mineral reserves from your balanced diet. Teeth buds beneath the gums are also calcifying in preparation for infancy.',
            'tip' => 'Ensure your daily nutrition includes ample dietary calcium from yogurt, fortified foods, or leafy greens.'
        ],
        [
            'title' => 'Firm Prods & Turns',
            'subtitle' => 'Less fluid room turns kicks into prominent rolls.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Because baby is occupying a greater proportion of the uterus, sudden sharp kicks are evolving into deliberate prods and rolls. You will often feel a distinct foot or elbow gliding smoothly across the contour of your abdomen. These tactile sensations provide clear reassurance of baby’s ongoing vigor and physical growth.',
            'tip' => 'Notice the regular times of day your baby stretches, rolls, or pushes against your belly wall.'
        ],
    ],
    30 => [
        [
            'title' => 'Brain Wrinkling & Grooves',
            'subtitle' => 'Neural pathways multiply exponentially every single day.',
            'iconType' => 'SOUND',
            'detail' => 'The cerebral cortex is multiplying neural pathways exponentially, carving deep fissures and convolutions across its surface. This wrinkling process maximizes cortical surface area, accommodating trillions of new synaptic connections. Baby’s bone marrow has also completely taken over primary red blood cell production.',
            'tip' => 'Eat balanced meals containing iron, folate, and B vitamins to support continuous red blood cell production.'
        ],
        [
            'title' => 'Temperature Regulation',
            'subtitle' => 'Brain centers start controlling self-thermal regulation.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Specialized brain centers in the hypothalamus are beginning to take over autonomic body temperature control. Subcutaneous fat stores continue to build up, helping baby maintain personal thermal stability after birth. The baby’s movements are strong and coordinated, reflecting robust neurological control.',
            'tip' => 'Dress in comfortable layers so you can easily adjust to your body’s shifting internal warmth.'
        ],
    ],
    31 => [
        [
            'title' => 'All Five Senses Online',
            'subtitle' => 'Sensory nerve signals are actively processed by the brain.',
            'iconType' => 'SOUND',
            'detail' => 'All five primary senses—sight, sound, taste, touch, and smell receptors—are actively transmitting signals to the brain. Baby can taste differences in fluid flavors, see light gradients, and hear conversational voices clearly. The central nervous system is integrating these multisensory inputs into unified cognitive experiences.',
            'tip' => 'Play soothing instrumental music or hum gently to provide peaceful sensory stimulation.'
        ],
        [
            'title' => 'Head Turning',
            'subtitle' => 'Baby turns neck side to side to locate sounds.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby has developed the neck muscle strength to turn their head side to side in response to external sounds and tactile pressure. Strong pushes with feet and knees against your ribs or flanks are common as baby finds comfortable positions. The hands frequently reach up to touch the face, lips, and ears.',
            'tip' => 'Change your resting posture or stretch gently if baby pushes firmly against your ribs.'
        ],
    ],
    32 => [
        [
            'title' => 'Deep Rhythmic Breathing',
            'subtitle' => 'Diaphragm practices breathing motions constantly.',
            'iconType' => 'SOUND',
            'detail' => 'Your baby spends significant portions of each day practicing continuous breathing motions with their diaphragm. These rhythmic exercises expand chest wall compliance and prepare respiratory muscles for room air. Fine toenails and fingernails have fully formed and reached the tips of their digits.',
            'tip' => 'Stay relaxed and take slow, deep breaths to maximize oxygenation for both you and your baby.'
        ],
        [
            'title' => 'Fingernails Fully Grown',
            'subtitle' => 'Tiny nails reach past the tips of baby’s fingers.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s delicate fingernails have now grown completely to the very tips of their tiny fingers. While baby explores their face in the womb, these soft nails can occasionally leave tiny, harmless surface scratches. Subcutaneous fat is smoothing out previous skin wrinkles, giving baby a delightfully plump appearance.',
            'tip' => 'Prepare a pair of soft baby mittens or infant nail files for your newborn hospital bag.'
        ],
    ],
    33 => [
        [
            'title' => 'Maternal Antibody Transfer',
            'subtitle' => 'Immune antibodies cross placenta for early newborn safety.',
            'iconType' => 'SOUND',
            'detail' => 'A surge of maternal antibodies, primarily immunoglobulin G, crosses the placenta to provide baby with temporary immune protection. This natural shield will help protect your newborn against common pathogens during the early months of life. Amniotic fluid volume has peaked and will remain steady as baby fills the remaining space.',
            'tip' => 'Discuss any recommended third-trimester immunizations with your healthcare provider to optimize antibody transfer.'
        ],
        [
            'title' => 'Cozy Fetal Curl',
            'subtitle' => 'With tight quarters, knees tuck securely to the chest.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Because baby now fills most of the uterine space, they adopt the classic fetal position with knees drawn up to the chest. While full somersaults are no longer possible, movements feel like deliberate rolls, shifts, and firm presses. Tracking these regular daily movements remains an important reassurance of wellbeing.',
            'tip' => 'Monitor your baby’s daily activity patterns, especially during their customary active evening hours.'
        ],
    ],
    34 => [
        [
            'title' => 'Central Nervous System',
            'subtitle' => 'Brain connections are robust and mature rapidly.',
            'iconType' => 'SOUND',
            'detail' => 'The central nervous system is achieving mature coordination between cardiac rhythm, respiration, and motor reflexes. Pupillary reflexes dilate and constrict in response to light, signaling advanced autonomic visual development. The lungs are producing abundant surfactant, an essential substance that prevents air sacs from collapsing.',
            'tip' => 'Pack your birth bag and keep emergency contact numbers easily accessible on your phone.'
        ],
        [
            'title' => 'Calm Rest Periods',
            'subtitle' => 'Baby sleeps deeply with established circadian rhythms.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s sleep periods are increasingly structured, with established cycles of quiet rest alternating with active wakefulness. When awake, baby practices blinking, sucking, swallowing, and reaching with their hands. These coordinated behaviors mirror the behavioral repertoire of a healthy newborn infant.',
            'tip' => 'Rest when your baby is quiet, and stay well hydrated to support your overall endurance.'
        ],
    ],
    35 => [
        [
            'title' => 'Kidney & Liver Maturity',
            'subtitle' => 'Metabolic organs are ready to process nutrients and waste.',
            'iconType' => 'SOUND',
            'detail' => 'Baby’s kidneys are fully mature and processing several cups of amniotic fluid daily, while the liver processes metabolic waste. The gastrointestinal tract is coated with meconium, the harmless greenish-black material that will form baby’s first bowel movement. The physical organs are essentially prepared for independent function.',
            'tip' => 'Eat smaller, more frequent meals to maintain steady energy without crowding your stomach.'
        ],
        [
            'title' => 'Rapid Weight Surge',
            'subtitle' => 'Baby gains about half a pound of healthy fat weekly.',
            'iconType' => 'MOVEMENT',
            'detail' => 'During these final weeks, baby gains approximately half a pound of healthy fat and muscle each single week. This rapid weight accumulation fills out skin folds and builds vital energy reserves for labor and post-birth feeding. You may feel strong pressure in your pelvis as baby nestles lower in the abdomen.',
            'tip' => 'Use a pelvic support belt or warm bath to relieve lower back and pelvic pressure.'
        ],
    ],
    36 => [
        [
            'title' => 'Cephalic Head Descent',
            'subtitle' => 'Baby settles head-down into pelvic brim for birth.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Most babies settle into the vertex, or head-down, position and begin descending into the pelvic brim. Known clinically as engagement or lightening, this descent often relieves pressure on your diaphragm, making breathing easier. You might notice increased pelvic pressure and more frequent visits to the restroom.',
            'tip' => 'Practice upright resting postures and gentle hip circles on a birth ball to encourage optimal positioning.'
        ],
        [
            'title' => 'Shedding Lanugo Coat',
            'subtitle' => 'Downy skin hair sheds into amniotic fluid naturally.',
            'iconType' => 'SOUND',
            'detail' => 'The fine downy lanugo hair that previously covered baby’s body is shedding, along with portions of the creamy vernix coating. Baby naturally swallows these shed particles, which accumulate safely in the digestive tract as meconium. The skin underneath is smooth, soft, and well-hydrated.',
            'tip' => 'Stay calm and practice relaxing breathing techniques to prepare your mind for the labor process.'
        ],
    ],
    37 => [
        [
            'title' => 'Early Term Readiness',
            'subtitle' => 'Most organ systems can function independently outside.',
            'iconType' => 'SOUND',
            'detail' => 'Reaching week 37 signifies that your baby has officially reached early term status. The lungs and digestive system are robustly mature and ready to sustain life outside the protective womb. While baby continues to add protective fat each day, they are physiologically prepared for birth.',
            'tip' => 'Review your labor signs, including regular contractions and water breaking, with your maternity provider.'
        ],
        [
            'title' => 'Firm Grasping Power',
            'subtitle' => 'Baby possesses a remarkably strong reflexive hand grasp.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby’s grasp reflex is fully developed and surprisingly strong, capable of firmly gripping small objects or fingers. Coordinated sucking and swallowing reflexes are completely aligned to support immediate post-birth breastfeeding or feeding. Movements may feel more like firm rolls and shifts due to snug quarters.',
            'tip' => 'Continue to feel for baby’s regular movements each day and contact your care team if activity changes.'
        ],
    ],
    38 => [
        [
            'title' => 'Vocal Cord Preparation',
            'subtitle' => 'Vocal cords are primed to let out baby’s first cry.',
            'iconType' => 'SOUND',
            'detail' => 'Baby’s vocal cords and larynx are primed and ready to produce that unforgettable first cry immediately upon delivery. That inaugural cry expands the lungs fully with air and triggers the miraculous transition to independent breathing. Baby’s brain continues to fine-tune autonomic control over body temperature and breathing rate.',
            'tip' => 'Take time to rest peacefully and conserve your physical and emotional energy for labor.'
        ],
        [
            'title' => 'Plump Cheeks for Feeding',
            'subtitle' => 'Cheek fat pads build up to assist newborn latching.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Prominent fat pads have formed in baby’s cheeks, giving them adorable plumpness and providing structural suction power for nursing. The skull bones remain soft, flexible, and separated by cranial sutures to safely navigate the birth canal. These cranial plates will gently mold during labor and return to shape soon after.',
            'tip' => 'Learn about newborn hunger cues and comfortable skin-to-skin practices for the first hours of life.'
        ],
    ],
    39 => [
        [
            'title' => 'Surfactant in Lungs',
            'subtitle' => 'Air sacs are coated to prevent collapse upon first breath.',
            'iconType' => 'SOUND',
            'detail' => 'Your baby is officially full term, with lungs producing peak amounts of protective surfactant coating. This surfactant ensures the microscopic alveoli open smoothly with the very first breath and stay inflated between breaths. The placenta continues providing vital oxygen and antibodies until the moment of birth.',
            'tip' => 'Stay well-nourished with easily digestible snacks and keep your support team on alert.'
        ],
        [
            'title' => 'Conserving Energy',
            'subtitle' => 'Baby rests calmly, gathering strength for birth day.',
            'iconType' => 'MOVEMENT',
            'detail' => 'Baby is resting comfortably in the snug pelvic cradle, storing vital glycogen reserves in the liver for labor day. You will still feel regular, rhythmic shifts and nudges, confirming that baby remains active and healthy. The journey of pregnancy is nearly complete as both of your bodies prepare for birth.',
            'tip' => 'Contact your labor unit or care provider promptly if your water breaks or contractions become regular.'
        ],
    ],
    40 => [
        [
            'title' => 'Full Term Due Date',
            'subtitle' => 'Baby has completed gestation and is ready to arrive!',
            'iconType' => 'SOUND',
            'detail' => 'Your due date has arrived, marking forty completed weeks of incredible growth and nurturing. Your baby is fully developed, measuring approximately twenty inches in length and ready to meet you at any moment. While only a small percentage of babies arrive precisely on their due date, birth is just around the corner.',
            'tip' => 'Trust your body and stay in close contact with your care team as you await the onset of labor.'
        ],
        [
            'title' => 'Eager to Meet You',
            'subtitle' => 'Baby will instantly recognize your comforting voice.',
            'iconType' => 'MOVEMENT',
            'detail' => 'When your baby takes their first breath outside the womb, they will immediately recognize the unique cadence of your voice. Months of listening in the womb have created an indelible bond that provides profound comfort during the transition to life outside. Soon you will hold your little one in your arms.',
            'tip' => 'Practice skin-to-skin contact immediately after birth to soothe your newborn and support early bonding.'
        ],
    ],
];

// Query database for week milestones
$stmt = $pdo->prepare('SELECT * FROM baby_development WHERE week = ?');
$stmt->execute([$week]);
$dev = $stmt->fetch();

if ($dev && !empty($dev['highlights'])) {
    $rawHighlights = is_string($dev['highlights']) ? json_decode($dev['highlights'], true) : $dev['highlights'];
    if (!is_array($rawHighlights)) {
        $rawHighlights = [];
    }

    $clampedWeek = max(1, min(40, $week));
    $highlights = [];
    foreach ($rawHighlights as $i => $item) {
        $id = $item['id'] ?? ($i + 1);
        $title = $item['title'] ?? 'Milestone';
        $subtitle = $item['subtitle'] ?? '';
        $iconType = $item['iconType'] ?? ($i % 2 === 0 ? 'SOUND' : 'MOVEMENT');

        $detail = $item['detail'] ?? null;
        $tip = $item['tip'] ?? null;

        if (empty($detail) || empty($tip)) {
            $fallback = $milestones[$clampedWeek][$i] ?? ($milestones[$clampedWeek][0] ?? null);
            if ($fallback) {
                if (empty($detail)) $detail = $fallback['detail'];
                if (empty($tip)) $tip = $fallback['tip'];
            } else {
                if (empty($detail)) {
                    $detail = "Your baby continues to undergo meaningful development during week {$week}. Key organ systems are maturing steadily to support healthy growth and physiological balance. These educational milestones provide insight into the typical weekly progress occurring throughout pregnancy.";
                }
                if (empty($tip)) {
                    $tip = "Maintain balanced daily nutrition, gentle activity, and open communication with your maternity care team.";
                }
            }
        }

        $highlight = array_merge($item, [
            'id' => $id,
            'title' => $title,
            'subtitle' => $subtitle,
            'iconType' => $iconType,
            'detail' => $detail,
            'tip' => $tip,
        ]);
        $highlights[] = $highlight;
    }
} else {
    // Generate educational milestone estimate if exact week not seeded
    $lengthEst = max(5, min(52, round(2.5 + ($week * 1.2))));
    $weightEst = max(10, min(3600, round(pow($week / 6.0, 3) * 60)));

    $clampedWeek = max(1, min(40, $week));
    $selected = $milestones[$clampedWeek] ?? $milestones[40];
    $highlights = [
        [
            'id' => 1,
            'title' => $selected[0]['title'],
            'subtitle' => $selected[0]['subtitle'],
            'iconType' => $selected[0]['iconType'],
            'detail' => $selected[0]['detail'],
            'tip' => $selected[0]['tip'],
        ],
        [
            'id' => 2,
            'title' => $selected[1]['title'],
            'subtitle' => $selected[1]['subtitle'],
            'iconType' => $selected[1]['iconType'],
            'detail' => $selected[1]['detail'],
            'tip' => $selected[1]['tip'],
        ]
    ];

    if (!$dev) {
        $dev = [
            'week' => $week,
            'subtitle' => "Week {$week} · Your baby's weekly development.",
            'length_cm_text' => "{$lengthEst} cm",
            'length_sub_text' => 'Head to heel · approximate',
            'weight_gram_text' => "{$weightEst} g",
            'weight_sub_text' => 'educational estimate',
        ];
    }
}

respond(200, [
    'success' => true,
    'data' => clean_dots([
        'currentWeek' => (int)$dev['week'],
        'weekSubtitle' => $dev['subtitle'],
        'lengthCmText' => $dev['length_cm_text'],
        'lengthSubText' => $dev['length_sub_text'] ?? 'Head to heel · approximate',
        'weightGramText' => $dev['weight_gram_text'],
        'weightSubText' => $dev['weight_sub_text'] ?? 'educational estimate',
        'highlights' => $highlights
    ])
]);
