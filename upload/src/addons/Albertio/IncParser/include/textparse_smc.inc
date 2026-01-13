#if defined _textparse_smc_included
	#endinput
#endif
#define _textparse_smc_included

/**
 * Parser invalid code.
 */
enum SMCParser
{
	Invalid_SMCParser = 0
};

/**
 * Parse result directive.
 */
enum SMCResult
{
	SMCParse_Continue,          /* Continue parsing */
	SMCParse_Halt,              /* Stop parsing here */
	SMCParse_HaltFail           /* Stop parsing and return failure */
};

/**
 * Parse error codes.
 */
enum SMCError
{
	SMCError_Okay = 0,          /* No error */
	SMCError_StreamOpen,        /* Stream failed to open */
	SMCError_StreamError,       /* The stream died... somehow */
	SMCError_Custom,            /* A custom handler threw an error */
	SMCError_InvalidSection1,   /* A section was declared without quotes, and had extra tokens */
	SMCError_InvalidSection2,   /* A section was declared without any header */
	SMCError_InvalidSection3,   /* A section ending was declared with too many unknown tokens */
	SMCError_InvalidSection4,   /* A section ending has no matching beginning */
	SMCError_InvalidSection5,   /* A section beginning has no matching ending */
	SMCError_InvalidTokens,     /* There were too many unidentifiable strings on one line */
	SMCError_TokenOverflow,     /* The token buffer overflowed */
	SMCError_InvalidProperty1   /* A property was declared outside of any section */
};

/**
 * Creates a new SMC parser.
 * This is used to set parse hooks.
 *
 * @return              A new handle to an SMC Parse structure.
 */
native SMCParser:SMC_CreateParser();

/**
 * Disposes of an SMC parser.
 *
 * @param handle        Handle to an SMC Parse structure.
 *
 * @return              True if disposed, false otherwise.
 */
native SMC_DestroyParser(&SMCParser:handle);

/**
 * Parses a config file.
 *
 * @param handle        A handle to an SMC Parse structure.
 * @param file          A string containing the file path.
 * @param line          An optional by reference cell to store the last line number read.
 * @param col           An optional by reference cell to store the last column number read.
 * @param data          An optional handle or value to pass through to callback functions
 *
 * @return              An SMCParseError result.
 * @error               Invalid or corrupt handle.
 */
native SMCError:SMC_ParseFile(SMCParser:handle, const file[], &line = 0, &col = 0, any:data = 0);

/**
 * Sets the SMC_ParseStart function of a parse handle.
 *
 * @note  Below is the prototype of callback:
 *        -
 *          Called when parsing is started.
 *
 *          .@param handle        Handle to an SMC Parse structure.
 *          .@param data          Handle or value passed in SMC_ParseFile
 *
 *          .@noreturn
 *
 *          public OnParseStart(SMCParser:handle, any:data)
 *        -
 * @param handle        Handle to an SMC Parse structure.
 * @param func          A ParseStart callback.
 *
 * @noreturn
 * @error               Invalid or corrupt handle.
 */
native SMC_SetParseStart(SMCParser:handle, const func[]);

/**
 * Sets the SMC_ParseEnd function of a parse handle.
 *
 * @note  Below is the prototype of callback:
 *        -
 *          Called when parsing is halted.
 *
 *          .@param handle        Handle to an SMC Parse structure.
 *          .@param halted        True if abnormally halted, false otherwise.
 *          .@param failed        True if parsing failed, false otherwise.
 *          .@param data          Handle or value passed in SMC_ParseFile
 *
 *          .@noreturn
 *
 *          public OnParseEnd(SMCParser:handle, bool:halted, bool:failed, any:data)
 *        -
 * @param handle        Handle to an SMC Parse structure.
 * @param func          A ParseEnd callback.
 *
 * @noreturn
 * @error               Invalid or corrupt handle.
 */
native SMC_SetParseEnd(SMCParser:handle, const func[]);

/**
 * Sets the three main reader functions.
 *
 * @note  Enclosing quotes are always stripped.
 * @note  Below is the prototype of callbacks:
 *        -
 *          NewSection:
 *              Called when the parser finds a new section or sub-section.
 *
 *              .@param handle           Handle to an SMC Parse structure.
 *              .@param name             String containing section name.
 *              .@param data             Handle or value passed in SMC_ParseFile
 *
 *              .@return                 An SMCResult action to take.
 *
 *              public SMCResult:OnNewSection(SMCParser:handle, const name[], any:data)
 *
 *          KeyValue:
 *              Called when the parser finds a new key/value pair.
 *
 *              .@param handle        Handle to an SMC Parse structure.
 *              .@param key           String containing key name.
 *              .@param value         String containing value name.
 *              .@param data          Handle or value passed in SMC_ParseFile
 *
 *              .@return              An SMCResult action to take.
 *
 *              public SMCResult:OnKeyValue(SMCParser:handle, const key[], const value[], any:data)
 *
 *          EndSection:
 *              Called when the parser finds the end of the current section.
 *
 *              .@param handle        Handle to an SMC Parse structure.
 *              .@param data          Handle or value passed in SMC_ParseFile
 *
 *              .@return              An SMCResult action to take.
 *
 *              public SMCResult:OnEndSection(SMCParser:handle, any:data)
 * -
 * @param handle        Handle to an SMC Parse structure.
 * @param kv            A KeyValue callback.
 * @param ns            An optional NewSection callback.
 * @param es            An optional EndSection callback.
 *
 * @noreturn
 */
native SMC_SetReaders(SMCParser:smc, const kvFunc[], const nsFunc[] = "", const esFunc[] = "");

/**
 * Sets a raw line reader on an text parser handle.
 *
 * @note  Below is the prototype of callbacks:
 *        -
 *          Called whenever a raw line is read.
 *
 *          .@param handle        Handle to an SMC Parse structure.
 *          .@param line          A string containing the raw line from the file.
 *          .@param lineno        The line number it occurs on.
 *          .@param data          Handle or value passed in SMC_ParseFile
 *
 *          .@return              An SMCResult action to take.
 *
 *          public SMCResult:SMC_RawLine(SMCParser:handle, const line[], lineno, any:data)
 *        -
 * @param handle        Handle to an SMC Parse structure.
 * @param func          A RawLine callback.
 *
 * @noreturn
 */
native SMC_SetRawLine(SMCParser:handle, const func[]);

/**
 * Gets an error string for an SMCError code.
 *
 * @note  SMCError_Okay returns false.
 * @note  SMCError_Custom (which is thrown on SMCParse_HaltFail) returns false.
 *
 * @param error         The SMCParseError code.
 * @param buffer        A string buffer for the error (contents undefined on failure).
 * @param buf_max       The maximum size of the buffer.
 *
 * @return              True on success, false otherwise.
 */
native bool:SMC_GetErrorString(SMCError:error, buffer[], buf_max);