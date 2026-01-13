#if defined _tsconst_included
  #endinput
#endif
#define _tsconst_included

#define TSMAX_WEAPONS 44	// 37 + throwing knife + brekable + 5 custom weapon slots

#define TSPWUP_NONE			0
#define TSPWUP_RANDOM			0
#define TSPWUP_SLOWMO 			1
#define TSPWUP_INFAMMO		 	2
#define TSPWUP_KUNGFU		 	4
#define TSPWUP_SLOWPAUSE 		8
#define TSPWUP_DFIRERATE		16
#define TSPWUP_GRENADE			32
#define TSPWUP_HEALTH			64
#define TSPWUP_ARMOR			128
#define TSPWUP_SUPERJUMP		256

#define TSITEM_KUNGFU			1<<0
#define TSITEM_SUPERJUMP		1<<1

#define TSKF_STUNTKILL			1<<0
#define TSKF_SLIDINGKILL		1<<1
#define TSKF_DOUBLEKILL			1<<2
#define TSKF_ISSPEC			1<<3
#define TSKF_KILLEDSPEC			1<<4

#define TSA_SILENCER			1
#define TSA_LASERSIGHT			2
#define TSA_FLASHLIGHT			4
#define TSA_SCOPE				8

#define TSMSG_NORMAL			6
#define TSMSG_WAITING			11
#define TSMSG_DEAD			1
#define TSMSG_KILLER			2
#define TSMSG_DEMOLITION		3
#define TSMSG_SPECIALIST		4
#define TSMSG_UNSTOPPABLE		5
#define TSMSG_THEONE			10

#define STUNT_NONE 0
#define STUNT_DUCK 1
#define STUNT_ROLL 2
#define STUNT_DIVE 3
#define STUNT_GETUP 4
#define STUNT_FLIP 5

enum {
  TSW_GLOCK18 = 1,
  TSW_UNK1,
  TSW_UZI,
  TSW_M3,
  TSW_M4A1,
  TSW_MP5SD,
  TSW_MP5K,
  TSW_ABERETTAS,
  TSW_MK23,
  TSW_AMK23,
  TSW_USAS,
  TSW_DEAGLE,
  TSW_AK47,
  TSW_57,
  TSW_AUG,
  TSW_AUZI,
  TSW_TMP,
  TSW_M82A1,
  TSW_MP7,
  TSW_SPAS,
  TSW_GCOLTS,
  TSW_GLOCK20,
  TSW_UMP,
  TSW_M61GRENADE,
  TSW_CKNIFE,
  TSW_MOSSBERG,
  TSW_M16A4,
  TSW_MK1,
  TSW_C4,
  TSW_A57,
  TSW_RBULL,
  TSW_M60E3,
  TSW_SAWED_OFF,
  TSW_KATANA,
  TSW_SKNIFE,
  TSW_KUNG_FU,
  TSW_TKNIFE
};