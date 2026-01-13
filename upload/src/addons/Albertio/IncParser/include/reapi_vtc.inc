#if defined _reapi_vtc_included
	#endinput
#endif
#define _reapi_vtc_included

/**
 * Checks whether the player is talking at the moment.
 *
 * @param index      Client index
 *
 * @return           true if client is speaking, false otherwise
 */
native bool:VTC_IsClientSpeaking(const index);

/**
 * Mutes the player.
 *
 * @param index      Client index
 *
 * @noreturn
 */
native VTC_MuteClient(const index);

/**
 * Unmutes the player.
 *
 * @param index      Client index
 *
 * @noreturn
 */
native VTC_UnmuteClient(const index);

/**
 * Checks whether the player is muted at the moment.
 *
 * @param index      Client index
 *
 * @return           true if client is muted, false otherwise
 */
native bool:VTC_IsClientMuted(const index);

/**
 * Play the audio file via the voice stream.
 *
 * @note Usage example: VTC_PlaySound(id, "sound/ambience/Opera.wav");
 *
 * @param receiver       Receiver index
 * @param soundFilePath  The path to the sound file
 *
 * @noreturn
 */
native VTC_PlaySound(const receiver, const soundFilePath[]);

/**
 * Called when the player started talking.
 *
 * @param index      Client index
 *
 * @noreturn
 */
forward VTC_OnClientStartSpeak(const index);

/**
 * Called when the player stopped talking.
 *
 * @param index      Client index
 *
 * @noreturn
 */
forward VTC_OnClientStopSpeak(const index);