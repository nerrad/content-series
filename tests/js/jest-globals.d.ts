declare const describe: ( name: string, fn: () => void ) => void;
declare const it: ( name: string, fn: () => void ) => void;
declare const test: ( name: string, fn: () => void ) => void;
declare const beforeEach: ( fn: () => void ) => void;
declare const afterEach: ( fn: () => void ) => void;
declare const expect: ( value: unknown ) => {
	toBe: ( expected: unknown ) => void;
	toEqual: ( expected: unknown ) => void;
	toHaveLength: ( expected: number ) => void;
	toContain: ( expected: unknown ) => void;
	toBeNull: () => void;
	toBeTruthy: () => void;
	toBeFalsy: () => void;
	toHaveBeenCalledTimes: ( expected: number ) => void;
	toHaveBeenCalledWith: ( ...expected: unknown[] ) => void;
};
declare const jest: {
	clearAllMocks: () => void;
	resetModules: () => void;
	doMock: ( moduleName: string, factory: () => unknown ) => void;
	fn: ( impl?: ( ...args: unknown[] ) => unknown ) => {
		( ...args: unknown[] ): unknown;
		mock: {
			calls: unknown[][];
		};
		mockReset: () => void;
		mockReturnValue: ( value: unknown ) => void;
	};
	isolateModules: ( fn: () => void ) => void;
};
