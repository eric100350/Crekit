<x-app-layout>



    <main>
        <style>
            body {
        font-family: arial;
        color: white;
        user-select: none;
        -moz-user-select: none;
    }
    .mainCanvas {
        position: absolute;
        top: 50px;
        left: 0px;
        background: #48C;

        background: radial-gradient(circle, rgba(134,95,95,1) 0%, rgba(105,103,147,1) 27%, rgba(103,125,152,1) 63%, rgba(126,126,194,1) 100%);

    }
        </style>

        <script>

            function $(com, props) {
                const elementAssign = (el, props) => {
                    if (props.style) {
                        const style = props.style;
                        delete props.style;
                        Object.assign(el, props);
                        Object.assign(el.style, style);
                        props.style = style;
                        return el;
                    }
                    return Object.assign(el, props);
                }
                if ($.isStr(com)) {
                    if (com[0] === "?") { // query
                        const qry = document.querySelectorAll(com.slice(1).trim());
                        if (isNaN(props)) { return [...qry] }
                        props = Number(props);
                        if (Number(props) < 0) { props = qry.length - props }
                        props = props < 0 ? 0 : props > qry.length - 1 ? qry.length - 1 : props;
                        return qry[prop]
                    }
                    com = com.toLowerCase();
                    com = com === "text" ? document.createTextNode(props) : document.createElement(com);
                }
                return $.isObj(props) ? elementAssign(com, props) : com;
            }
            function $$(el, ...sibs) {
                var idx = 0, where = 1;
                while (idx < sibs.length) {
                    const sib = sibs[idx++];
                    if ($.isNum(sib)) { where = Number(sib) }
                    else if ($.isStr(sib)) { sibs[--idx] = $("text", sib) }
                    else if (where <= 0) { el.insertBefore(sib, el.firstChild) }
                    else { el.appendChild(sib) }
                }
                return el;
            }
            function $R(fromEl, ...els){
                for (const el of els) {
                    fromEl.removeChild(el);
                }
                return fromEl;
            }
            $.isObj = val => typeof val === "object" && !Array.isArray(val) && val !== null;
            $.isNum = val => !isNaN(val);
            $.isStr = val => typeof val === "string";
            $$.INSERT = 0;
            $$.APPEND = 1;
            const infoStyle = {color: "white", fontFamily: "arial", fontSize: "20px", zIndex:1, position: "absolute", pointerEvents: "none",  display: "none"};
            const helpEls = [
                $("div",{textContent: "info", style: {...infoStyle, bottom: "200px", left: "80px", display: null, zIndex: 1000, pointerEvents: null}}),
                $("div",{textContent: "", style: {...infoStyle, bottom: "10px", display: null, zIndex: 1000, pointerEvents: null}}),
                $("div",{textContent: "Keys [0] to rack up. [1] to save position. [2] to recall positions", style: {...infoStyle, bottom: "80px"}}),
                $("div",{textContent: "After shot while balls moving hold [RIGHT] to accelerate time", style: {...infoStyle, bottom: "60px"}}),
                $("div",{textContent: "Mouse [LEFT hold] to power [LEFT hold then RIGHT] Fine aim. [Release] shots", style: {...infoStyle, bottom: "40px"}}),
                $("div",{textContent: "[Right click] Setup mode. Then click to pick ball, click to place. Place cue ball to play", style: {...infoStyle, bottom: "20px"}}),
            ];
            $$(document.body, $("div",{textContent: "NEW GAME IN TOWN ", style: {...infoStyle, display: null}}));
            $$(document.body, $("div",{textContent: "CREKIT POOL GAME", style: {...infoStyle, right: "10px", fontSize: "16px", display: null}}));
            $$(document.body, ...helpEls);
            var helpOn = false;
            const showHelp = () => { var i = 1; while (i < helpEls.length) { helpEls[i++].style.display = null } };
            const hideHelp = () => { var i = 1; while (i < helpEls.length) { helpEls[i++].style.display = "none" } };
            helpEls[0].addEventListener("mouseover", () => {if (!helpOn) { showHelp() }});
            helpEls[0].addEventListener("mouseout", () => {if (!helpOn) { hideHelp() }});
            helpEls[0].addEventListener("click", () => {if (!helpOn) { helpOn = true; showHelp() } else { helpOn = false; hideHelp() }});
            function startMouse(supressContext = true, useWheel = true, offsetX = 0, offsetY = 0) {
                const events = ["mousedown","mouseup","mousemove"];
                useWheel && events.push("wheel");
                var eHandler;
                const m = {
                    x: 0, y: 0, oldX: 0, oldY: 0, button: 0, buttonOld: 0, wheel: 0, wheelTotal: 0, alt: false, ctrl: false, shift: false, over: false,
                    set extHandler(h) { if (h instanceof Function) { eHandler = h } },
                    get extHandler() { return eHandler },
                    isOver(el) {
                        const bounds = el.getBoundingClientRect;
                        return m.x >= bounds.left && m.x < bounds.right && m.y >= bounds.top && m.y < bounds.bottom;
                    },
                };
                function mouseEvent(e){
                    m.x = e.pageX + offsetX;
                    m.y = e.pageY + offsetY;
                    m.alt = e.altKey;
                    m.ctrl = e.ctrlKey;
                    m.shift = e.shiftKey;
                    if (e.type === "mousedown") { m.buttonOld = m.button; m.button |= 1 << (e.which - 1) }
                    else if(e.type === "mouseup") { m.buttonOld = m.button; m.button &= ~(1 << (e.which - 1)) }
                    else if (e.type === "wheel") { m.wheelTotal += (m.wheel = Math.sign(-e.deltaY)) }
                    eHandler && eHandler(e);
                }
                function cancelable(e) { e.preventDefault() }
                useWheel && document.addEventListener("wheel", cancelable, {passive: false});
                supressContext && document.addEventListener("contextmenu", cancelable);
                events.forEach(name => document.addEventListener(name, mouseEvent, {passive: true}));
                document.addEventListener("mouseout", (e) => { eHandler && eHandler(e) }, {passive: true});
                document.addEventListener("mouseover", (e) => { eHandler && eHandler(e) }, {passive: true});
                return m;
            }
            function simpleKeyboard() {
                const keys = {};
                var downCount = 0;
                function keyEvent(e) {
                    const keyDown = e.type === "keydown"
                    if(keys[e.code] !== undefined) {
                        keys[e.code] = keyDown;
                        e.preventDefault();
                    }
                    if(keys.anyKey !== undefined) {
                        downCount += keyDown ? 1 : -1;
                        keys.anyKey = keyDown > 0;
                    }
                }
                const API = {
                    keys,
                    clear() {for(const name of Object.keys(keys)) { keys[name] = false }},
                    addKey(...names) { for(const name of names) { keys[name] = false }; return keys },
                };
                document.addEventListener("keydown", keyEvent);
                document.addEventListener("keyup", keyEvent);
                return API;
            }
            function Touch(forElement = window) {
                const directions = { UNKNOWN: 0, UP: 1, RIGHT: 2, DOWN: 3, LEFT: 4 };
                const HANDLERS = { touchmove: updatePoints, touchstart: updatePoints, touchend: removePoints };
                const points = new Map();
                function Point(touch) {
                    this.x = this.startX = touch.pageX;
                    this.y = this.startY = touch.pageY;
                    this.dy = this.dx = 0;
                    this.dist = 0;
                    this.dirAA = directions.UNKNOWN;
                    this.id = touch.identifier;
                    this.uid = uid++;
                }
                Point.prototype = {
                    update(touch) {
                        this.x = touch.pageX;
                        this.y = touch.pageY;
                        const dx = this.dx = this.x - this.startX;
                        const dy = this.dy = this.y - this.startY;
                        this.dist = (dx * dx + dy * dy) ** 0.5;
                        if (this.dist > 0) {
                            this.dirAA = Math.abs(dx) > Math.abs(dy) ?
                                (dx < 0 ? directions.LEFT : directions.RIGHT) :
                                (dy < 0 ? directions.UP : directions.DOWN);
                            this.direction = Math.atan2(dx, dy);
                        } else {
                            this.dirAA = directions.UNKNOWN;
                        }

                    }
                }
                var preventDefault = false;
                var hasTouched = false;
                var uid = 0;
                const API = {
                    directions,
                    get points() { return [...points.values()] },
                    get hasPoints() { return points.size > 0 },
                    set preventDefault(val) { if (val !== preventDefault) { preventDefault = val; defaultSet() } },
                    get hasTouched() { return hasTouched },
                };

                function defaultSet() {
                    if (document === forElement) {
                        forElement.body.style.touchAction = preventDefault ? "none" : null;
                    } else {
                        forElement.style.touchAction = preventDefault ? "none" : null;
                    }
                }
                function removePoints(updates) {
                    var idx = updates.length;
                    while (idx-- > 0) { points.delete(updates[idx].identifier) }
                }
                function updatePoints(updates) {
                    var p, idx = updates.length;
                    while (idx-- > 0) {
                        const touch = updates[idx];
                        const id = touch.identifier;
                        p = points.get(id);
                        !p && points.set(id, p = new Point(touch));
                        p.update(touch);
                    }
                }
                function handleEvent(e) {
                    HANDLERS[e.type](e.changedTouches);
                    preventDefault && e.preventDefault();
                    hasTouched = true;

                }
                forElement.addEventListener("touchstart", handleEvent, false);
                forElement.addEventListener("touchend", handleEvent, false);
                forElement.addEventListener("touchmove", handleEvent, false);
                return API;
            }
            const touches = Touch(document);

            var autoStart = true;


            const keyboard = simpleKeyboard();
            keyboard.addKey("Digit1", "Digit2", "Digit0",);
            const keys = keyboard.keys;
            mathExt(); // creates some additional math functions
            var allowSpinControl = false;
            var slowDevice = false;
            const HAS_GAME_PLAY_API = false;
            const CUSH_W = 47, CUSH_H = 23.5;
            const CUSH_REFERENCE_SIZE = 24;
            var MIN_SIZE = Math.min(innerWidth / CUSH_W, innerHeight / CUSH_W);
            var CUSH_SIZE_X = MIN_SIZE | 0;
            var CUSH_SIZE_Y = MIN_SIZE | 0;
            var INSET = CUSH_SIZE_X * 3.5;
            var TABLE_DIMOND_SIZE = CUSH_SIZE_X * 0.2;
            var TABLE_CANVAS_SIZE = {width: CUSH_SIZE_X * CUSH_W + INSET * 2, height: CUSH_SIZE_Y * CUSH_H + INSET * 2}
            var TABLE_SCALE = Math.min((innerWidth * (2/3)) / TABLE_CANVAS_SIZE.width, (innerHeight * (2/3)) / TABLE_CANVAS_SIZE.height);

            var BALL_SIZE = CUSH_SIZE_X ;
            var BALL_SIZE_SQR = BALL_SIZE * BALL_SIZE;
            var MASS_SCALE =  CUSH_REFERENCE_SIZE / CUSH_SIZE_X;
            var BALL_MASS =  4 / 3 * Math.PI * (BALL_SIZE ** 3) * MASS_SCALE;
            const POCKET_SIZE = 1.76;   // in cush units
            const POCKET_ROLL_IN_SCALE = 1.3;  // scales size of pocket roll
            var POCKET_SIZE_PX = POCKET_SIZE * BALL_SIZE;
            var MARK_SIZE = BALL_SIZE * (200 / 256);  // radius of white part of ball
            var MARK_SIZE_S = BALL_SIZE * (105 / 256);  // radius of white part of ball
            var MOUSE_LENGTH = BALL_SIZE * (CUSH_H + 4); // the pool que
            var MOUSE_TIP = BALL_SIZE / 6;
            var MOUSE_END = BALL_SIZE / 3;
            const TABLE_MARK_COLOR = "#ADB8";
            const TABLE_MARK_LINE_WIDTH = 3;
            const SHOW_GUIDES = false;  // Do not set to true as functions have been removed from code pen version
            const TABLE_COLOR = "#080";
            const TABLE_COLORS = ["#2A3","#293","#283","#273", "#263"];
            const WHITE_BALL = "#D8D6D4";
            const SHADOW_COLOR = "#0004";
            const LIGHT_COLOR_LOW = "#FFF4";
            const LIGHT_COLOR = "#FFF6";
            const CUE_DARK_COLOR = "#842";
            const CUE_LIGHT_COLOR = "#CB6";
            const CUE_JOIN_COLOR = "#CA2";
            const DIMOND_COLOR =  "#CB9";
            const DIMOND_COLOR_OUTLINE = "#642";
            const VEL_MIN = 1;
            const VEL_MAX = 5;
            var SHADE_X = Math.cos(-Math.PI * 0.25) * BALL_SIZE;
            var SHADE_Y = Math.sin(-Math.PI * 0.25) * BALL_SIZE;
            var MESSAGE_FONT_SIZE = BALL_SIZE * 1.5;
            var MESSAGE_OUTLINE = MESSAGE_FONT_SIZE * 0.2;
            var MESSAGE_OFFSET = 0;
            const rack = [  //  x, y, id start positions and id (id AKA type)
               10, 1, 0,
               -4, 0, 2,
               -2, 1, 9,  -2,-1, 3,
               0, 2, 4,   0, 0, 1,   0,-2, 10,
               2, 3, 11,  2, 1, 5,   2,-1, 12,   2, -3, 8,
               4, 4, 7,   4, 2, 14,  4, 0, 13,   4, -2, 6,  4,-4,15,
            ];
            const rackCenter = {x: 0, y: 0}; // value is set when table is created
            const head = {x: 0, y: 0, Dr: 0}; // value is set when table is created. Dr is D radius
            const BALL_COLORS = {
                white:  WHITE_BALL,
                yellow: "#CC2",
                blue:   "#12D",
                red:    "#E31",
                purple: "#A2D",
                green:  "#3B1",
                brown:  "#962",
                orange: "#D73",
                black:  "#000",
            };
            const colors = [ // by ball idx in rack order
               WHITE_BALL,
               BALL_COLORS.black,
               BALL_COLORS.yellow, BALL_COLORS.blue, BALL_COLORS.red, BALL_COLORS.purple,  BALL_COLORS.green, BALL_COLORS.brown,   BALL_COLORS.orange,
               BALL_COLORS.yellow, BALL_COLORS.blue, BALL_COLORS.red, BALL_COLORS.purple,  BALL_COLORS.green, BALL_COLORS.brown,   BALL_COLORS.orange,
            ];
            const PW = POCKET_SIZE * 1.4, PW1 = POCKET_SIZE * 1.2, PW11 = POCKET_SIZE * 1.1, PW2 = POCKET_SIZE;
            const PC = CUSH_W / 2, PI = 0.1, PI1 = 0.3, PI11 = 0.45, PI3 = 0.6;
            const cush = [ // cushion pairs of coordinates forming top left corner (top left pocket and half top center pocket
                [0,      PW], [-PI,      PW1], [-PI1,      PW11],  [-PI11, PW2],      [-PI3 * 3, 0.5],  [-3 ,-1],
                [-1,    -3],  [0.5, -PI3 * 3], [PW2,       -PI11], [PW11, -PI1],      [PW1, -PI],       [PW, 0],
                [PC - PW, 0], [PC - PW1, -PI], [PC - PW11, -PI1],  [PC - PW2, -PI11], [PC - PW2, -PI3], [PC - PW2, -PI3 * 3], [PC - PW2, -4],
            ];
            cush.push(... cush.map(xy => [CUSH_W - xy[0], xy[1]]).reverse());
            cush.push(... cush.map(xy => [xy[0], CUSH_H - xy[1]]).reverse());
            const MAX_RESOLUTION_CYCLES = 1200;  // debug inifinit loop protection
            const SHOW_COLLISION_TIME = 30;      // debug
            var TABLE_TOP = 0;
            var TABLE_LEFT =  0;
            var TABLE_BOTTOM = CUSH_SIZE_Y * (CUSH_H);
            var TABLE_RIGHT = CUSH_SIZE_X * (CUSH_W);
            const DOWN_DISPLAY_BALL_SPACING = 2;
            const DOWN_DISPLAY_BALL_SCALE = 2 / 3;
            const DOWN_DISPLAY_BALL_OFFSET = 2.2;
            var ctx;
            const canvas = $("canvas", {className: "mainCanvas", width: innerWidth, height: innerHeight});
            const gameCanvas = $("canvas", TABLE_CANVAS_SIZE);
            const overlay = $("canvas", TABLE_CANVAS_SIZE);
            const sprites = $("canvas", {width: BALL_SIZE * 8, height: BALL_SIZE * 3});
            const ballDownCan = $("canvas", {width: BALL_SIZE * (16 * DOWN_DISPLAY_BALL_SPACING), height: BALL_SIZE * 2});

            const ctxMain = canvas.getContext("2d");

            const ctxGame = ctx = gameCanvas.getContext("2d");
            const spriteCtx = sprites.getContext("2d");
            const ballDownCtx = ballDownCan.getContext("2d");
            var downCount = 0;
            sprites.layout = {};
            $$(document.body, canvas);
            const defaultPlayer = {
                CUE_DARK_COLOR: "#842",
                CUE_LIGHT_COLOR: "#CB6",
                CUE_JOIN_COLOR: "#CA2",
            };
            const game = undefined;
            var GAME_TOP = (canvas.height - TABLE_CANVAS_SIZE.height * TABLE_SCALE) / 2;
            var GAME_LEFT = (canvas.width - TABLE_CANVAS_SIZE.width * TABLE_SCALE) / 2;
            const SHOW_POWER_BAR = true; // Shows simple power bar if true. Power bar is just an approximation of power ATM
            const powerBar = {
                max: {
                    RGB1: {r: 100, g: 0, b: 0},
                    RGB2: {r: 255, g: 80, b: 80},
                },
                power: {
                    RGB1: {r: 255, g: 255, b: 0},
                    RGB2: {r: 255, g: 255, b: 255},
                },
            }
            // mouse AKA que
            const mouse = Object.assign(startMouse(true, true), {
                pull: 0,
                spring: 0,
                speed: 0,
                pos: 0,
                cueHit: 0,
                angle: 0,
                spin: 0,
                spinPower: 0,
                mass: (MOUSE_TIP + MOUSE_END) * 0.5  * Math.PI * MOUSE_LENGTH * MASS_SCALE
            });
            var maxPull = BALL_SIZE * BALL_MASS / mouse.mass;
            var wait = 0, tableEdge, tableGradient, tempQueBall, tempBall, tableClear = false, placeBalls = false, ballToPlace;
            var message = "Welcome to CREKIT POOL GAME .";
            const messages = [
                "lets get to know the rules and how to play CREKIT",
                "This is one move game, and it by breaking the rack",
                "You get to choose the position of the black ball in the rack ",
                "Right click the cue ball to enter placement mode  ",

                "Left click & drag que for power, release to shoot.",
                "While balls are moving hold right button to speed up time.",
                "Right click to enter placement mode.",


            ];
            var messageTime = 250;
            var lockAngle = false;
            var lockAngleLocked = false;
            var lockDistTemp = 1;
            var lockAngleAt = 0;
            var fineAngleStart = 0;
            var fineAngle = 0;
            var runToStop = 1;
            var frameCount = 0;
            var balls = [], lines = [], pockets = [], contacts = [], positionSaves = [];
            positionSaves.current = 0;
            setTimeout(() => {
                resize();
                requestAnimationFrame(mainLoop);
                if (game) {
                    game.update();
                    game.startGame();
                }
            }, 0);
            addEventListener("resize", resize);
            function resize() {
                balls = [];
                lines = [];
                pockets = [];
                contacts = [];

                MIN_SIZE = Math.min(innerWidth / CUSH_W, innerHeight / CUSH_W);
                CUSH_SIZE_X = MIN_SIZE | 0;
                CUSH_SIZE_Y = MIN_SIZE | 0;
                INSET = CUSH_SIZE_X * 3.5;
                TABLE_DIMOND_SIZE = CUSH_SIZE_X * 0.2;
                TABLE_CANVAS_SIZE = {width: CUSH_SIZE_X * CUSH_W + INSET * 2, height: CUSH_SIZE_Y * CUSH_H + INSET * 2}
                TABLE_SCALE = Math.min((innerWidth * (6/7)) / TABLE_CANVAS_SIZE.width, (innerHeight * (6/7)) / TABLE_CANVAS_SIZE.height);

                BALL_SIZE = CUSH_SIZE_X ;
                BALL_SIZE_SQR = BALL_SIZE * BALL_SIZE;
                MASS_SCALE =  CUSH_REFERENCE_SIZE / CUSH_SIZE_X;
                BALL_MASS =  4 / 3 * Math.PI * (BALL_SIZE ** 3) * MASS_SCALE;

                POCKET_SIZE_PX = POCKET_SIZE * BALL_SIZE;
                MARK_SIZE = BALL_SIZE * (200 / 256);  // radius of white part of ball
                MARK_SIZE_S = BALL_SIZE * (105 / 256);  // radius of white part of ball
                MOUSE_LENGTH = BALL_SIZE * (CUSH_H + 4); // the pool que
                MOUSE_TIP = BALL_SIZE / 6;
                MOUSE_END = BALL_SIZE / 3;

                TABLE_TOP = 0;
                TABLE_LEFT =  0;
                TABLE_BOTTOM = CUSH_SIZE_Y * (CUSH_H);
                TABLE_RIGHT = CUSH_SIZE_X * (CUSH_W);

                Object.assign(canvas, {width: innerWidth, height: innerHeight});
                Object.assign(gameCanvas, TABLE_CANVAS_SIZE);
                Object.assign(overlay, TABLE_CANVAS_SIZE);
                Object.assign(sprites, {width: BALL_SIZE * 8, height: BALL_SIZE * 3});
                Object.assign(ballDownCan, {width: BALL_SIZE * 16 *  DOWN_DISPLAY_BALL_SPACING, height: BALL_SIZE * 4});

                GAME_TOP = (canvas.height - TABLE_CANVAS_SIZE.height * TABLE_SCALE) / 2;
                GAME_LEFT = (canvas.width - TABLE_CANVAS_SIZE.width * TABLE_SCALE) / 2;

                mouse.mass = (MOUSE_TIP + MOUSE_END) * 0.5  * Math.PI * MOUSE_LENGTH * MASS_SCALE;
                maxPull = BALL_SIZE * BALL_MASS / mouse.mass;
                MESSAGE_FONT_SIZE = Math.max(BALL_SIZE * 1.5, 14);
                MESSAGE_OUTLINE = MESSAGE_FONT_SIZE * 0.2;
                MESSAGE_OFFSET = Math.max(0, MESSAGE_FONT_SIZE * 2.2 - GAME_TOP);

                createSprites();
                tableEdge = createTable();  // returned is path2d so corners can be transparent
                const diagDist = (ctxGame.canvas.width  ** 2 + ctxGame.canvas.height ** 2) ** 0.5 * 0.5;
                tableGradient = ctx.createRadialGradient(ctxGame.canvas.width / 2, ctxGame.canvas.height / 2, 0, ctxGame.canvas.width / 2, ctxGame.canvas.height / 2, diagDist );
                tableGradient.addColorStop(0, TABLE_COLOR);
                tableGradient.addColorStop(0.7, TABLE_COLOR);
                tableGradient.addColorStop(0.9, TABLE_COLORS[3]);
                tableGradient.addColorStop(1, TABLE_COLORS[4]);

                rackBalls();


            }
            const toHEX = int8 => (int8 < 16 ? "0" : "") + int8.toString(16);
            function cssHexCol(RGB1, RGB2, pos) {
                const r = (RGB2.r - RGB1.r) * pos + RGB1.r | 0;
                const g = (RGB2.g - RGB1.g) * pos + RGB1.g | 0;
                const b = (RGB2.b - RGB1.b) * pos + RGB1.b | 0;
                return "#" + toHEX(r) + toHEX(g) + toHEX(b);

            }
            function Line(x1,y1,x2,y2) {
                this.isBehindPocket =
                    (x1 < TABLE_LEFT - CUSH_SIZE_X * 1 || y1 < TABLE_TOP - CUSH_SIZE_Y * 1 || y1 > TABLE_BOTTOM + CUSH_SIZE_Y * 1 || x1 > TABLE_RIGHT + CUSH_SIZE_X * 1) &&
                    (x2 < TABLE_LEFT - CUSH_SIZE_X * 1|| y2 < TABLE_TOP - CUSH_SIZE_Y * 1|| y2 > TABLE_BOTTOM + CUSH_SIZE_Y * 1 || x2 > TABLE_RIGHT + CUSH_SIZE_X * 1);
                x1 += INSET;
                y1 += INSET;
                x2 += INSET;
                y2 += INSET;

                this.x1 = x1;
                this.y1 = y1;
                this.x2 = x2;
                this.y2 = y2;
                this.vx = this.x2 - this.x1;
                this.vy = this.y2 - this.y1;
                this.lenInv = 1 / (this.vx * this.vx + this.vy * this.vy) ** 0.5;
                this.u = 0;
            }
            Line.prototype = {
                intercept(ball) { // only if ball approching from right side (as if standing on start looking to end). Undefined if no intercept
                    const x = this.vx, y = this.vy;
                    const d = ball.vx * y - ball.vy * x;
                    if (d > 0) {  // only if moving towards the line
                        const rScale = BALL_SIZE * this.lenInv;
                        const nx = ball.y - (this.y1 + x * rScale);
                        const ny = ball.x - (this.x1 - y * rScale);
                        const u1 = this.u = (ball.vx * nx - ball.vy * ny) / d;
                        if (u1 >= 0 && u1 <= 1) {  return (x * nx - y * ny) / d }
                        let xe, ye;
                        if (u1 > -rScale && u1 < 0) {
                            xe = this.x1;
                            ye = this.y1;
                        }
                        if (u1 > 1 && u1 < 1 + rScale) {
                            xe = this.x2;
                            ye = this.y2;
                        }
                        if (xe!== undefined) { // if near ends of line check end point as vector intercept circle
                            const vx = ball.vx, vy = ball.vy, v1Sqr = vx * vx + vy * vy;
                            const xx = ball.x - this.x1, yy = ball.y - this.y1, blSqr = xx * xx + yy * yy;
                            var b = -2 * (xx * vx + yy * vy);
                            const c = 2 * v1Sqr;
                            const d = (b * b - 2 * c * (blSqr - BALL_SIZE_SQR)) ** 0.5;
                            if (isNaN(d)) { return }
                            return (b - d) / c;
                        }
                    }
                }
            }
            function Ball(x, y, id) {
                this.x = x;
                this.y = y;
                this.z = 0;
                this.vx = 0;
                this.vy = 0;
                this.id = id;
                this.col = colors[id];
                this.center = {x:0, y:0, z:1};  // center of stripe
                this.centerS = {x:1, y:0, z:0}; // center of circle
                this.roll = {x:0, y: Math.rand(0,Math.TAU), z: Math.rand(0,Math.TAU)};
                this.applyRoll();
                this.dead = false;
                this.hold = false;
                this.inPocket = false;
            }
            Ball.prototype = {
                update() {
                    var da, roll, spx = this.vx, spy = this.vy;
                    const vx = this.vx;
                    const vy = this.vy;
                    const sSqr = vx * vx + vy * vy, speed = sSqr ** 0.5;
                    const tSqr = sSqr / TABLE_SCALE, tSpeed = speed / TABLE_SCALE;
                    if (tSpeed > 0.1) {
                        if (tSpeed < 4) {
                            da = (tSqr * (4.5 + 9 * 4 - tSqr)) / BALL_MASS;
                        } else {
                            da = (tSqr * 4.5) / BALL_MASS;  // accel due to drag
                        }
                        const nx = vx / speed;
                        const ny = vy / speed;
                        this.vx -= nx * da;
                        this.vy -= ny * da;
                        this.vx *= 0.993;
                        this.vy *= 0.993;
                    } else {
                        this.vx *= 0.9;
                        this.vy *= 0.9;
                    }
                    this.testPockets();
                    this.speed = (this.vx * this.vx + this.vy * this.vy) ** 0.5;
                    const dir = Math.atan2(this.vy, this.vx);
                    this.roll.z = dir;
                    this.roll.y =  this.speed / BALL_SIZE;
                    this.x += this.vx;
                    this.y += this.vy;
                    this.applyRoll();
                },
                testPockets() {
                    var nearPocket = false, idx= 0, pIdx;
                    if (this.z > 0.8 && ( this.x < TABLE_LEFT || this.y < TABLE_TOP || this.y > TABLE_BOTTOM || this.x > TABLE_RIGHT)) {
                        nearPocket = true;
                        this.z = 1;
                    } else {
                        for (const p of pockets) {
                            const px = p.x - this.x;
                            const py = p.y - this.y;
                            const dist = (px * px + py * py) ** 0.5;
                            if (dist < POCKET_SIZE_PX * POCKET_ROLL_IN_SCALE) {
                                const a = (1 - dist / (POCKET_SIZE_PX* POCKET_ROLL_IN_SCALE)) ** 1.2;
                                this.vx = this.vx * (1 - (a * 0.2)) + px / dist * a;
                                this.vy = this.vy * (1 - (a * 0.2)) + py / dist * a;
                                this.z = a ** 3;
                                nearPocket = true;
                                pIdx = idx;
                                break;
                            }
                            idx ++;
                        }
                    }
                    if (nearPocket) {
                        if (this.z > 0.8) { this.downPocket() }
                    } else { this.z = 0 }
                },
                downPocket() {
                    this.vx = 0;
                    this.vy = 0;
                    this.x = this.startX - 10000;
                    this.y = this.startY;
                    game && (game.pocketed = this);
                    if (this.id === 0) {
                        this.hold = true;
                        this.z = 0;
                        this.spin = 0;
                        this.inPocket = true;
                    } else  {
                        if (!this.dead) {
                            renderBall(ballDownCtx, (downCount + 1) * BALL_SIZE * 2, BALL_SIZE * 1.5, this);
                            downCount++;
                        }
                        this.dead = true;
                    }
                },
                applyRoll() { // rotate in direction of movement for visuals only
                    var c = this.center;
                    var xd = Math.cos(this.roll.z);
                    var yd = Math.sin(this.roll.z);
                    const cpy = Math.cos(this.roll.y);
                    const spy = Math.sin(this.roll.y);
                    var x = xd * c.x + yd * c.y;    // in roll direction space
                    var y = xd * c.y - yd * c.x;
                    var xx = x * cpy - c.z * spy;   // rotate
                    c.z = x * spy + c.z * cpy;
                    c.x = xd * xx - yd * y;         // back to world space
                    c.y = xd * y  + yd * xx;
                    if (this.id > 8 || !this.id) {  // rotate inner circle
                        c = this.centerS;
                        x = xd * c.x + yd * c.y;
                        y = xd * c.y - yd * c.x;
                        xx = x * cpy - c.z * spy
                        c.z = x * spy + c.z * cpy;
                        c.x = xd * xx - yd * y
                        c.y = xd * y  + yd * xx;
                    }
                },
                applyRotate() { // rotates only about Z axis
                    var c = this.center;
                    const ax = Math.cos(this.roll.z);
                    const ay = Math.sin(this.roll.z);
                    var x = c.x, y = c.y;
                    c.x = x * ax - y * ay;
                    c.y = x * ay + y * ax;
                    if (this.id > 8 || !this.id) {
                        c = this.centerS;
                        x = c.x;
                        y = c.y;
                        c.x = x * ax - y * ay;
                        c.y = x * ay + y * ax;
                    }
                },
                drawSprite(spr, offX, offY, scale = 1) {
                    if (this.inPocket) { return }
                    const w = spr.w, h = spr.w;
                    ctx.setTransform(scale,0,0,scale, this.x + offX,  this.y + offY);
                    ctx.drawImage(sprites, spr.x, spr.y, w, h, - w / 2,  - h / 2, w, h);
                },
                render(scale = 1) {
                    if (this.inPocket) { return }
                    var cx, cy, cz;
                    if (this.id === 0) {
                        ctx.fillStyle = this.col;
                        ctx.beginPath();
                        ctx.arc(this.x, this.y, BALL_SIZE, 0, Math.PI * 2);
                        ctx.fill();
                    }
                    const c = this.center;
                    const cS = this.centerS;
                    ctx.setTransform(scale, 0, 0, scale, this.x, this.y);
                    ctx.fillStyle = this.col;
                    ctx.beginPath();
                    ctx.arc(0, 0, BALL_SIZE, 0, Math.PI * 2);
                    ctx.fill();
                    if (this.id) {
                        if (this.id > 8) {
                            this.drawSection(c.x,c.y,c.z, MARK_SIZE)
                            this.drawSection(cS.x,cS.y,cS.z, MARK_SIZE_S)
                        } else {
                            this.drawSection(c.x,c.y,c.z, MARK_SIZE_S)
                        }
                    } else if (allowSpinControl){
                        this.drawSection(c.x,c.y,c.z, BALL_SIZE * 0.2, "#cdf")
                        this.drawSection(cS.x,cS.y,cS.z, BALL_SIZE * 0.2, "#CCf")
                    }
                    if (this.z > 0) {
                        ctx.fillStyle = "#000";
                        ctx.globalAlpha =  (this.z < 0 ? 0 : this.z> 1 ? 1 : this.z);
                        ctx.beginPath();
                        ctx.arc(0, 0, BALL_SIZE, 0, Math.PI * 2);
                        ctx.fill();
                        ctx.globalAlpha = 1;
                    }
                },
                drawSection(cx, cy, cz, sr, col = WHITE_BALL) { // sr section radius
                    const R = BALL_SIZE / sr;
                    var len = (cx * cx + cy * cy) ** 0.5;
                    len = len < -1 ? -1 : len > 1 ? 1 : len;
                    const eDir = Math.atan2(cy, cx), rDir = eDir + Math.PI;
                    const pheta = Math.asin(len);
                    var A = Math.cos(Math.asin(1 / R)) * R;
                    var tx = Infinity;
                    const c1 = Math.sin(pheta) * A;
                    const c2 = 1 / (Math.cos(pheta) ** 2);
                    const roots = Math.quadRoots(c2 - 1, -2 * c1 * c2, c1 * c1 * c2 + R * R - 1.001);
                    roots.length > 0 && (tx = (roots.length === 1 ? roots[0]: (roots[0] + roots[1]) * 0.5) * sr);
                    const exr = Math.abs(Math.cos(pheta)) * sr;
                    A *= sr;
                    const x = cx * A, y = cy * A;
                    ctx.fillStyle = col;
                    ctx.beginPath();
                    if (tx >= BALL_SIZE) {
                        cz < 0 ?
                            ctx.ellipse( x,  y, exr, sr, eDir, 0, Math.TAU):
                            ctx.ellipse(-x, -y, exr, sr, eDir, 0, Math.TAU);
                    } else {
                        const ab = Math.acos(tx / BALL_SIZE);
                        const bb = Math.acos((tx - len * A) / exr);
                        if (cz < 0) {
                            ctx.arc(0, 0,  BALL_SIZE, eDir - ab, eDir + ab);
                            ctx.ellipse( x,  y, exr, sr, eDir, bb, -bb + Math.TAU);
                        } else {
                            ctx.arc(0, 0,  BALL_SIZE, rDir - ab, rDir + ab);
                            ctx.ellipse(-x, -y, exr, sr, rDir, bb, - bb);
                        }
                        ctx.fill();
                        ctx.beginPath();
                        if (cz > 0) {
                            ctx.arc(0, 0, BALL_SIZE, eDir - ab, eDir + ab);
                            ctx.ellipse( x,  y, exr, sr, eDir, bb, -bb + Math.TAU, true);
                        } else {
                            ctx.arc(0, 0,  BALL_SIZE, rDir - ab, rDir + ab);
                            ctx.ellipse(-x, -y, exr, sr, rDir, bb, - bb, true);
                        }
                    }
                    ctx.fill();
                },
                interceptBallTime(b, time) {
                    const x = this.x - b.x;
                    const y = this.y - b.y;
                    const d = (x * x + y * y) ** 0.5;
                    if (d > BALL_SIZE * 2) {
                        const t = Math.circlesInterceptUnitTime(
                            this.x, this.y, this.vx, this.vy,
                            b.x, b.y, b.vx, b.vy,
                            BALL_SIZE, BALL_SIZE
                        );
                        if (t >= time && t <= 1) { return t }
                    }
                },
                collideLine(l, time, lineU, notInPlay = false) {  // lineU is unit position on line. If outside 0-1 then has hit end points of line
                    var x1, y1;
                    this.x += this.vx * time;
                    this.y += this.vy * time;
                    if (lineU < 0 || lineU > 1) { // if end point use line to end point rotated 90deg as line
                        if (lineU < 0) {
                            x1 = -(l.y1 - this.y);
                            y1 = l.x1 - this.x;
                        } else {
                            x1 = -(l.y2 - this.y);
                            y1 = l.x2 - this.x;
                        }
                    } else {
                        x1 = l.x2 - l.x1;
                        y1 = l.y2 - l.y1;
                    }
                    const d = (x1 * x1 + y1 * y1) ** 0.5;
                    const nx = x1 / d;
                    const ny = y1 / d;
                    const u = (this.vx  * nx + this.vy  * ny) * 2;
                    this.vx = (nx * u - this.vx);
                    this.vy = (ny * u - this.vy);
                    this.x -= this.vx * time;
                    this.y -= this.vy * time;
                    if (l.isBehindPocket && !notInPlay) { this.downPocket() }
                },
                collide(b, time) {  // Ball hits ball at time. ( time == 0 == previouse frame, time == 1 == this frame )
                    const a = this;
                    a.x = a.x + a.vx * time;
                    a.y = a.y + a.vy * time;
                    b.x = b.x + b.vx * time;
                    b.y = b.y + b.vy * time;
                    const x = a.x - b.x, y = a.y - b.y;
                    const d = (x * x + y * y);
                    const u1 = a.vx * x + a.vy * y;
                    const u2 = a.vy * x - a.vx * y;
                    const u3 = b.vx * x + b.vy * y;
                    const u4 = b.vy * x - b.vx * y;
                    b.vx = (x * u1 - y * u4) / d;
                    b.vy = (y * u1 + x * u4) / d;
                    a.vx = (x * u3 - y * u2) / d;
                    a.vy = (y * u3 + x * u2) / d;
                    a.x = a.x - a.vx * time;
                    a.y = a.y - a.vy * time;
                    b.x = b.x - b.vx * time;
                    b.y = b.y - b.vy * time;
                },
                advancePos(time, speed) {
                    this.x = this.x + this.vx * time;
                    this.y = this.y + this.vy * time;
                    if (speed > 0) {
                        const s = (this.vx * this.vx + this.vy * this.vy) ** 0.5;
                        if (s > 0) {
                            this.vx = (this.vx / s) * speed;
                            this.vy = (this.vy / s) * speed;
                        }
                    }
                },
                shadowOf() { return {id: this.id, x: this.x, y: this.y, dead: this.dead} },
                fromShadow(shadow) { Object.assign(this, {...shadow, vx: 0, vy: 0, z:0}) }

            }

            function canAdd(ball) { // test if safe to add ball (no overlap)
                if (ball.x < TABLE_LEFT + INSET + BALL_SIZE || ball.y < TABLE_TOP + INSET + BALL_SIZE ||
                    ball.y > TABLE_BOTTOM + INSET - BALL_SIZE || ball.x > TABLE_RIGHT+ INSET - BALL_SIZE) {
                    return false;
                }

                for (const b of balls) {
                    if (ball !== b && ((b.x - ball.x) ** 2 + (b.y - ball.y) ** 2) < (BALL_SIZE_SQR * 4)) { return false }
                }
                return true;
            }
            function isInD(ball) {
                if (ball.x <= head.x) {
                    const dx = ball.x - head.x;
                    const dy = ball.y - head.y;
                    if (dx * dx + dy * dy < head.Dr * head.Dr) {return true}
                }
                return false;
            }
            function isOffTable(x, y) {
                if (x < TABLE_LEFT + INSET || y < TABLE_TOP + INSET ||
                    y > TABLE_BOTTOM + INSET || x > TABLE_RIGHT+ INSET) {
                    return true;
                }
                return false;
            }
            function renderBall(ctxDest, x, y, ball) {
                const c = ctx;
                ctx = ctxDest;
                const bx = ball.x;
                const by = ball.y;
                ball.x = x;
                ball.y = y;
                ball.z = 0;
                ball.render(TABLE_SCALE);
                ball.drawSprite(sprites.layout.shade, 0, 0, TABLE_SCALE);
                ctx.globalCompositeOperation = "lighter";
                ctx.globalAlpha = 1/1.5;
                ball.drawSprite(sprites.layout.light, 0, 0, TABLE_SCALE);
                ctx.globalAlpha = 1;
                ball.drawSprite(sprites.layout.spec, 0, 0, TABLE_SCALE);
                ctx.globalAlpha = 1;
                ctx.globalCompositeOperation = "source-over";
                ctx = c;
                ball.x = bx;
                ball.y = by;

            }
            function renderBalls() {
                for (const b of balls) {
                    if (!b.inPocket) {
                        if (b.hold && b.id !== 0) {
                            b.drawSprite(sprites.layout.shadow, BALL_SIZE * 0.8, BALL_SIZE * 0.8);
                        } else {
                            b.drawSprite(sprites.layout.shadow, BALL_SIZE * 0.4, BALL_SIZE * 0.4);
                        }
                    }
                }
                ctx.setTransform(1,0,0,1,0,0);
                ctx.drawImage(overlay, 0, 0);
                for (const b of balls) { b.render() }
                for (const b of balls) { b.drawSprite(sprites.layout.shade, 0, 0) }
                ctx.globalCompositeOperation = "lighter";
                ctx.globalAlpha = 1/1.5;
                for (const b of balls) { b.drawSprite(sprites.layout.light, 0, 0) }
                ctx.globalAlpha = 1;
                for (const b of balls) { b.drawSprite(sprites.layout.spec, 0, 0) }
                ctx.setTransform(1,0,0,1,0,0);
                ctx.globalAlpha = 1;
                ctx.globalCompositeOperation = "source-over";
            }
            const sunk = HAS_GAME_PLAY_API ? {} : {}
            function renderQue(ball) {  // ball is the que target
                const B = ball;
                const cp = game ? game.current : defaultPlayer;
                var dx = Math.cos(mouse.angle);
                var dy = Math.sin(mouse.angle);
                const y = -dx;
                const x =  dy;
                const xx = x * BALL_SIZE;
                const yy = y * BALL_SIZE;
                const tt = MOUSE_TIP, te = MOUSE_END, tm = (tt + te) / 2; // taper tip, end, mid
                const joint = 5 / TABLE_SCALE;
                var dd = mouse.pos;
                var tip = (BALL_SIZE * 1.4 + dd);
                var mid = (MOUSE_LENGTH / 2 + dd);
                var end = (MOUSE_LENGTH + dd);

                ctx.lineCap = "round";
                ctx.lineJoin = "round";

                ctx.beginPath();
                ctx.lineTo(dx * end + B.x + BALL_SIZE * 2 +  x * tm  * 0.7, dy * end + B.y + BALL_SIZE * 2 + y * tm * 0.7);
                ctx.lineTo(dx * tip + B.x + BALL_SIZE / 2, dy * tip + B.y + BALL_SIZE / 2);
                ctx.lineTo(dx * end + B.x + BALL_SIZE * 2 -  x * tm * 0.7, dy * end + B.y + BALL_SIZE * 2 - y * tm * 0.7);
                ctx.closePath();
                ctx.strokeStyle = "#0002";
                ctx.lineWidth = tm * 1.5;
                ctx.stroke();

                ctx.beginPath();
                ctx.strokeStyle = "#05A";
                ctx.moveTo(dx * (tip - 1) + B.x, dy * (tip - 1) + B.y);
                ctx.lineTo(dx * (tip) + B.x, dy * (tip) + B.y);
                ctx.lineWidth = MOUSE_TIP * 2.5;
                ctx.stroke();
                ctx.strokeStyle = "#07D";
                ctx.lineWidth = MOUSE_TIP * 2;
                ctx.stroke();

                ctx.lineCap = "butt";
                ctx.beginPath();
                ctx.lineWidth = 1/ TABLE_SCALE;
                ctx.strokeStyle = "#000";
                ctx.fillStyle = cp.CUE_LIGHT_COLOR
                ctx.moveTo(dx * tip + B.x + x * tt, dy * tip + B.y + y * tt);
                ctx.lineTo(dx * (mid - joint) + B.x + x * tm, dy * (mid - joint) + B.y + y * tm);
                ctx.lineTo(dx * (mid - joint) + B.x - x * tm, dy * (mid - joint) + B.y - y * tm);
                ctx.lineTo(dx * tip + B.x - x * tt, dy * tip + B.y - y * tt);
                ctx.closePath();
                ctx.stroke();
                ctx.fill();
                ctx.strokeStyle = "#0003";
                ctx.lineWidth = 5 / TABLE_SCALE;
                ctx.stroke();

                ctx.beginPath();
                ctx.strokeStyle = "#000";
                ctx.fillStyle = cp.CUE_DARK_COLOR;
                ctx.lineWidth = 2 / TABLE_SCALE;
                ctx.moveTo(dx * (mid + joint) + B.x + x * tm, dy * (mid + joint) + B.y + y * tm);
                ctx.lineTo(dx * end + B.x + x * te, dy * end + B.y + y * te);
                ctx.lineTo(dx * end + B.x - x * te, dy * end + B.y - y * te);
                ctx.lineTo(dx * (mid + joint) + B.x - x * tm, dy * (mid + joint) + B.y - y * tm);
                ctx.stroke();
                ctx.fill();
                ctx.strokeStyle = "#0003";
                ctx.lineWidth = 5 / TABLE_SCALE;
                ctx.stroke();

                ctx.beginPath();
                ctx.strokeStyle = cp.CUE_JOIN_COLOR;
                ctx.moveTo(dx * (mid - joint) + B.x, dy * (mid - joint) + B.y);
                ctx.lineTo(dx * (mid + joint) + B.x, dy * (mid + joint) + B.y);
                ctx.lineWidth = tm * 2;
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(dx * tip + B.x + x * tt * dx * 0.5, dy * tip + B.y + y * tt * dx * 0.5);
                ctx.lineTo(dx * end + B.x + x * te * dx * 0.5, dy * end + B.y + y * te * dx * 0.5);
                ctx.globalCompositeOperation = "screen";
                ctx.strokeStyle =  LIGHT_COLOR_LOW;
                ctx.lineWidth = tt * 2;
                ctx.stroke();

                ctx.lineWidth = tt ;
                ctx.stroke();
                 ctx.strokeStyle = LIGHT_COLOR;
                ctx.lineWidth = tt / 2;
                ctx.stroke();

                ctx.globalCompositeOperation = "source-over"
                if (lockAngle && !lockAngleLocked) {
                    const A = mouse.angle;
                    const D = 50 / TABLE_SCALE;
                    const DS = 10 / TABLE_SCALE;
                    const DSa = DS / D;
                    ctx.fillStyle = "#F777";
                    ctx.strokeStyle = "#AAAA";
                    ctx.lineWidth = 2 / TABLE_SCALE;
                    ctx.beginPath();
                    ctx.arc(B.x, B.y, D + DS, A + DSa, A + DSa * 10)
                    ctx.lineTo(...Math.polarArray(A + DSa * 10, D + DS * 2, B.x, B.y))
                    ctx.lineTo(...Math.polarArray(A + DSa * 12, D + DS * 0, B.x, B.y))
                    ctx.lineTo(...Math.polarArray(A + DSa * 10, D + DS * -2, B.x, B.y))
                    ctx.arc(B.x, B.y, D - DS, A + DSa * 10,  A + DSa, true)
                    ctx.closePath();
                    ctx.fill();
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.arc(B.x, B.y, D + DS, A - DSa, A - DSa * 10, true)
                    ctx.lineTo(...Math.polarArray(A - DSa * 10, D + DS * 2, B.x, B.y))
                    ctx.lineTo(...Math.polarArray(A - DSa * 12, D + DS * 0, B.x, B.y))
                    ctx.lineTo(...Math.polarArray(A - DSa * 10, D + DS * -2, B.x, B.y))
                    ctx.arc(B.x, B.y, D - DS, A - DSa * 10,  A - DSa)
                    ctx.closePath();
                    ctx.fill();
                    ctx.stroke();
                }
                if (lockAngle && lockAngleLocked && lockDistTemp < -BALL_SIZE * 2) {
                    ctx.fillStyle = "#F777";
                    ctx.strokeStyle = "#AAAA";
                    ctx.lineWidth = 2 / TABLE_SCALE;
                    ctx.beginPath();
                    const D = 50 / TABLE_SCALE;
                    const DS = 10 / TABLE_SCALE;
                    const DSa = (DS / (D - DS)) * 0.5;
                    ctx.arc(B.x, B.y, D, 0, Math.TAU)
                    ctx.moveTo(...Math.polarArray(Math.PI * 0.75 + DSa, D - DS, B.x, B.y))
                    ctx.arc(B.x, B.y, D- DS, Math.PI * 0.75 + DSa, Math.PI * 1.75 - DSa);
                    ctx.closePath();
                    ctx.moveTo(...Math.polarArray(Math.PI * 1.75 + DSa, D - DS, B.x, B.y))
                    ctx.arc(B.x, B.y, D- DS, Math.PI * 1.75 + DSa, Math.PI * 2.75 - DSa);
                    ctx.closePath();
                    ctx.fill("evenodd");
                    ctx.stroke();
                }
            }
            function simpleGuide(ball) {
                const B = ball;
                var bx = Math.cos(mouse.angle) * BALL_SIZE;
                var by = Math.sin(mouse.angle) * BALL_SIZE;
                ctx.save()
                ctx.beginPath();
                ctx.rect(INSET, INSET, TABLE_RIGHT, TABLE_BOTTOM);
                ctx.clip();
                ctx.beginPath();
                ctx.strokeStyle = "#fff4";
                ctx.lineWidth = 1 / TABLE_SCALE;
                ctx.beginPath();
                ctx.lineTo(B.x - bx, B.y - by);
                ctx.lineTo(B.x - 2000 * bx, B.y - 2000 * by);
                ctx.moveTo(B.x - bx * 0.5 - by, B.y - by * 0.5 + bx);
                ctx.lineTo(B.x - 2000 * bx - by, B.y - 2000 * by + bx);
                ctx.moveTo(B.x - bx * 0.5 + by, B.y - by * 0.5 - bx);
                ctx.lineTo(B.x - 2000 * bx + by, B.y - 2000 * by - bx);
                ctx.stroke();
                ctx.restore();
            }
            function createSprites() {
                const ctx = spriteCtx;
                const ballShadowGrad = ctx.createRadialGradient(0,0, BALL_SIZE * 0.4, 0,0, BALL_SIZE * 1.2);
                ballShadowGrad.addColorStop(0,"#0006");
                ballShadowGrad.addColorStop(0.1,"#0006");
                ballShadowGrad.addColorStop(0.5,"#0005");
                ballShadowGrad.addColorStop(0.8,"#0003");
                ballShadowGrad.addColorStop(1,"#0000");
                ctx.fillStyle = ballShadowGrad;
                ctx.setTransform(1,0,0,1, BALL_SIZE * 1.5, BALL_SIZE * 1.5);
                ctx.beginPath();
                ctx.arc(0, 0, BALL_SIZE * 1.2, 0, Math.TAU);
                ctx.fill();
                sprites.layout.shadow = {x:0, y:0, w: BALL_SIZE * 3, h: BALL_SIZE * 3};

                const ballShadeGrad = ctx.createRadialGradient(-BALL_SIZE * 0.2, -BALL_SIZE * 0.2, BALL_SIZE * 0.8, -BALL_SIZE * 0.7, -BALL_SIZE * 0.7, BALL_SIZE * 2);
                ballShadeGrad.addColorStop(0,"#0000");
                ballShadeGrad.addColorStop(0.1,"#0000");
                ballShadeGrad.addColorStop(0.7,"#0005");
                ballShadeGrad.addColorStop(0.95,"#0004");
                ctx.fillStyle = ballShadeGrad;
                ctx.setTransform(1,0,0,1, BALL_SIZE * 4, BALL_SIZE);
                ctx.beginPath();
                ctx.arc(0, 0, BALL_SIZE, 0, Math.TAU);
                ctx.fill();
                sprites.layout.shade = {x: BALL_SIZE * 3, y:0, w: BALL_SIZE * 2, h: BALL_SIZE * 2};
                const ballSkyGrad = ctx.createRadialGradient(BALL_SIZE * 0.7, BALL_SIZE * 0.7, 0, 0, 0, BALL_SIZE * 2.4);
                ballSkyGrad.addColorStop(0,"#FFF0");
                //ballSkyGrad.addColorStop(0.3,"#FFF0");
                ballSkyGrad.addColorStop(0.4,"#EEF3");
                ballSkyGrad.addColorStop(0.6,"#DEF6");
                ballSkyGrad.addColorStop(0.7,"#CDF8");
                ballSkyGrad.addColorStop(0.8,"#FFF0");
                ballSkyGrad.addColorStop(1,"#FFF0");
                ctx.fillStyle = ballSkyGrad;
                ctx.setTransform(1,0,0,1, BALL_SIZE * 6, BALL_SIZE);
                ctx.beginPath();
                ctx.arc(0, 0, BALL_SIZE * 0.92, 0, Math.TAU);
                ctx.fill();
                sprites.layout.light = {x: BALL_SIZE * 5, y:0, w: BALL_SIZE * 2, h: BALL_SIZE * 2};

                ctx.setTransform(1,0,0,1, BALL_SIZE * 8, BALL_SIZE);
                const R = BALL_SIZE * 0.4
                ctx.fillStyle = LIGHT_COLOR_LOW;
                ctx.globalAlpha = 1/2;
                var i = 0.1;
                while (i < 1)  {
                    const size = (1 - i ** 4) * 0.5 + 0.2
                    ctx.beginPath();
                    ctx.ellipse(-R, -R, R * 0.6 * size, R * 0.9 * size, Math.PI / 4, 0 , Math.TAU);
                    ctx.fill();
                    i += 0.2;
                }
                sprites.layout.spec = {x: BALL_SIZE * 7, y:0, w: BALL_SIZE * 2, h: BALL_SIZE * 2};
            }
            function createTable() {  // renders table overlay and creates edge lines and pockets
                function drawDimonds(size, col, colS) {
                    var i = 1;
                    const xStep = (CUSH_W / 8) * CUSH_SIZE_X;
                    const yStep = (CUSH_H / 4) * CUSH_SIZE_Y;
                    const offsetY = CUSH_SIZE_Y * 2.0;
                    const offsetX = CUSH_SIZE_X * 2.0;
                    rackCenter.x = 6 * xStep + INSET;
                    rackCenter.y = 2 * yStep + INSET;
                    head.x = 2 * xStep + INSET;
                    head.y = 2 * yStep + INSET;
                    head.Dr =  yStep;
                    ctx.fillStyle = col;
                    ctx.strokeStyle = colS;
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    while (i < 8) {
                        const x = INSET + (i * xStep);
                        ctx.moveTo(x + size, -offsetY + INSET);
                        ctx.arc(x, -offsetY + INSET, size, 0, Math.TAU);
                        ctx.moveTo(x + size, CUSH_SIZE_Y * CUSH_H + INSET + offsetY);
                        ctx.arc(x, CUSH_SIZE_Y * CUSH_H + INSET + offsetY, size, 0, Math.TAU);
                        if (i < 4) {
                            const y = INSET + (i * yStep);
                            ctx.moveTo(-offsetX + size + INSET, y);
                            ctx.arc(-offsetX + INSET, y, size, 0, Math.TAU);
                            ctx.moveTo(CUSH_SIZE_X * CUSH_W + offsetX + size + INSET, y);
                            ctx.arc(CUSH_SIZE_X * CUSH_W + offsetX + INSET, y, size, 0, Math.TAU);
                        }

                        i ++;
                    }

                    ctx.stroke();
                    ctx.fill();
                    head.path = new Path2D;
                    head.path.lineTo(head.x, INSET);
                    head.path.lineTo(head.x, CUSH_H * CUSH_SIZE_Y + INSET);
                    head.path.moveTo(head.x, head.y + head.Dr);
                    head.path.arc(head.x, head.y, head.Dr, Math.PI * 0.5, Math.PI * 1.5);
                    head.path.moveTo(rackCenter.x + TABLE_MARK_LINE_WIDTH/2, rackCenter.y);
                    head.path.arc(rackCenter.x , rackCenter.y, TABLE_MARK_LINE_WIDTH/2, 0, Math.TAU);

                }
                function drawPocket(x, y, dir, pocketCoverIn) {
                    const cx = CUSH_SIZE_X, cy = CUSH_SIZE_Y;
                    x = x * cy + INSET;
                    y = y * cx + INSET;
                    const g = ctx.createRadialGradient(x, y, cx / 2, x, y, cx * 1.7);
                    g.addColorStop(0, "#000");
                    g.addColorStop(0.2, "#000C");
                    g.addColorStop(0.4, "#000B");
                    g.addColorStop(0.97, TABLE_COLORS[4] + "9");
                    g.addColorStop(0.98, TABLE_COLORS[3] + "6");
                    g.addColorStop(0.99, TABLE_COLORS[2] + "0");
                    g.addColorStop(1, TABLE_COLORS[0] + "0");
                    ctx.fillStyle = g;
                    ctx.beginPath();
                    ctx.arc(x, y, cx * 1.7, 0, Math.TAU);
                    ctx.fill();
                    pockets.push({x,y,r: cx * 1.7})

                    const C = 0.3; // chamfer
                    const B = 2.6; // back
                    const PCI = pocketCoverIn * cx;
                    const ax = Math.cos(dir), ay = Math.sin(dir);
                    ctx.setTransform(ax, ay, -ay, ax, x, y);
                    ctx.shadowOffsetX = 2;
                    ctx.shadowOffsetY = 2;
                    ctx.shadowBlur = 3;
                    ctx.shadowColor = "#0004"
                    ctx.fillStyle = "#444";
                    ctx.strokeStyle = "#888";

                    ctx.beginPath();
                    ctx.lineTo(-cx * (3.0 - C), PCI);
                    ctx.lineTo(-cx * 3.0, PCI - cy * C);
                    if (PCI === 0) {
                        ctx.lineTo(-cx * 3.0,  - cy * B + cy * C);
                        ctx.lineTo(-cx * (3.0 - C),  - cy * B);
                        ctx.lineTo( cx * (3.0 - C),  - cy * B);
                        ctx.lineTo( cx * 3.0,  - cy * B + cy * C);
                    } else {
                        ctx.lineTo(-cx * 3.0,  - cy * B + cy * C * 2.5);
                        ctx.lineTo(-cx * (3.0 - C * 2.5),  - cy * B);
                        ctx.lineTo( cx * (3.0 - C * 2.5),  - cy * B);
                        ctx.lineTo( cx * 3.0,  - cy * B + cy * C * 2.5);
                    }
                    ctx.lineTo( cx * 3.0, PCI - cy * C);
                    ctx.lineTo( cx * (3.0 - C), PCI);
                    ctx.lineTo( cx * (1.5 + C), PCI);
                    ctx.lineTo( cx * 1.5, PCI - cy * C);
                    ctx.arc(0, -cy * (PCI ? 0.0 : 0.25), cx * 1.5, Math.TAU - (PCI ? 0.0 : 0.25), Math.PI + (PCI ? 0.0 : 0.25), true);
                    ctx.lineTo(-cx * 1.5, PCI - cy * C);
                    ctx.lineTo(-cx * (1.5 + C), PCI);
                    ctx.closePath();
                    ctx.stroke();
                    ctx.fill();
                    ctx.setTransform(1,0,0,1,0,0);

                }
                function tableEdge(ctx) {
                    ctx.lineTo(    CSx * 0.5, CSy * 3.2);
                    ctx.lineTo(    CSx * 3.2, CSy * 0.5);
                    ctx.lineTo(w - CSx * 3.2, CSy * 0.5);
                    ctx.lineTo(w - CSx * 0.5, CSy * 3.2);
                    ctx.lineTo(w - CSx * 0.5, h - CSy * 3.2);
                    ctx.lineTo(w - CSx * 3.2, h - CSy * 0.5);
                    ctx.lineTo(CSx * 3.2,     h - CSy * 0.5);
                    ctx.lineTo(CSx * 0.5,     h - CSy * 3.2);
                }
                function createOutline() {
                    var i = 0, outline = new Path2D();
                    while (i < cush.length) { outline.lineTo(INSET + cush[i][0] * CUSH_SIZE_X, INSET + cush[i++][1] * CUSH_SIZE_Y) }
                    outline.closePath();
                    outline.rect(- 200, -100, w + 400, h + 400);
                    return outline;
                }

                var i = 0, j = 0;
                const ctx = overlay.getContext("2d");
                const w = ctx.canvas.width, w2 = w / 2;
                const h = ctx.canvas.height;
                const p = BALL_SIZE * 2; // pocket size
                const I = INSET;
                const outline = createOutline();
                while(i < cush.length) {
                    const x1 = cush[i][0] * CUSH_SIZE_X, y1 = cush[i++][1] * CUSH_SIZE_Y;
                    const x2 = cush[i % cush.length][0] * CUSH_SIZE_X, y2 = cush[i % cush.length][1] * CUSH_SIZE_Y;
                    lines.push( new Line(x1 , y1, x2 , y2));
               }


                ctx.save();
                ctx.beginPath();
                ctx.rect(0,0,w, h);
                ctx.clip();
                ctx.fillStyle = TABLE_COLORS[0];
                ctx.fill(outline, "evenodd");
                ctx.globalCompositeOperation = "source-atop";
                ctx.lineJoin = "round";
                ctx.strokeStyle = TABLE_COLORS[1];
                ctx.lineWidth = 16;
                ctx.stroke(outline);
                ctx.strokeStyle = TABLE_COLORS[2];
                ctx.lineWidth = 12;
                ctx.stroke(outline);
                ctx.strokeStyle = TABLE_COLORS[3];
                ctx.lineWidth = 8;
                ctx.stroke(outline);
                ctx.strokeStyle = TABLE_COLORS[4];
                ctx.lineWidth = 4;
                ctx.stroke(outline);
                ctx.strokeStyle = TABLE_COLORS[1];
                ctx.globalCompositeOperation = "lighter";
                ctx.lineWidth = 8;
                ctx.strokeStyle = "#CFC";
                ctx.globalAlpha = 1/16;
                ctx.setTransform(1,0,0,1,4,4);
                ctx.stroke(outline);
                ctx.lineWidth = 6;
                ctx.stroke(outline);
                ctx.globalAlpha = 1;
                ctx.setTransform(1,0,0,1,0,0);
                ctx.globalCompositeOperation = "destination-in";
                ctx.fill(outline, "evenodd");
                ctx.globalCompositeOperation = "destination-over";
                ctx.shadowColor = SHADOW_COLOR;
                ctx.shadowOffsetX = BALL_SIZE * 0.5;
                ctx.shadowOffsetY = BALL_SIZE * 0.5;
                ctx.shadowBlur = BALL_SIZE * 0.5;
                ctx.fill(outline, "evenodd");
                ctx.shadowColor = "#0000";

                ctx.globalCompositeOperation = "destination-out";
                ctx.fillStyle = "#a74"
                const CSx = CUSH_SIZE_X
                const CSy = CUSH_SIZE_Y
                ctx.beginPath();
                tableEdge(ctx)
                ctx.rect(-CSx * 2, -CSy * 2, w + CSx * 4, h + CSy * 4);
                ctx.fill("evenodd");

                ctx.globalCompositeOperation = "source-atop";
                ctx.beginPath();
                tableEdge(ctx)
                ctx.rect(CSx * 2, CSy * 2, w - CSx * 4, h - CSy * 4);
                ctx.fill("evenodd");


                ctx.globalCompositeOperation = "source-atop";
                ctx.beginPath();
                tableEdge(ctx)
                ctx.closePath()
                ctx.lineWidth = 8;
                ctx.strokeStyle = "#863";
                ctx.stroke();
                ctx.strokeStyle = "#532";
                ctx.lineWidth = 4;
                ctx.stroke();
                ctx.globalCompositeOperation = "lighter";
                ctx.lineWidth = 2;
                ctx.fillStyle = "#4448";
                ctx.fillRect(w - CSx * 2, CSy * 2, 2, h - CSy * 4);
                ctx.fillRect(CSx * 2, h - CSy * 2, w - CSy * 4, 2);
                 ctx.globalCompositeOperation = "multiply";
                 ctx.fillStyle = "#AAA6";
                ctx.fillRect(CSx * 2-2, CSy * 2, 2, h - CSy * 4);
                ctx.fillRect(CSx * 2, CSy * 2-2, w - CSy * 4, 2);

                 ctx.restore();
                drawDimonds(TABLE_DIMOND_SIZE, DIMOND_COLOR, DIMOND_COLOR_OUTLINE);

                const pI = 0.707 * 0.5;
                drawPocket(-pI,         -pI,            -Math.PI * 0.25, 1);
                drawPocket(CUSH_H,      -1.2,           0, 0);
                drawPocket(CUSH_W + pI, -pI,            Math.PI * 0.25, 1);
                drawPocket(-pI,         CUSH_H + pI,    Math.PI * 1.25, 1);
                drawPocket(CUSH_H,      CUSH_H + 1.2,   Math.PI, 0);
                drawPocket(CUSH_W + pI, CUSH_H + pI,    Math.PI * 0.75,1);

                const edge = new Path2D;
                tableEdge(edge);
                return edge;
            }
            function rackBalls() {
                const w = ctx.canvas.width, w2 = w / 2;
                const h = ctx.canvas.height;
                const p = BALL_SIZE * 2;
                var i = 0, j = 0, ball, add, e;
                balls.length = 0;
                while (i < rack.length) {
                    add = false;
                    e = 100;
                    while (!add && e--) {
                        ball = new Ball(
                            i ? rack[i] * BALL_SIZE * (0.90 + ((Math.random()**2 - 0.5) * 0.04)) + rackCenter.x : rack[i] * BALL_SIZE,
                            rack[i + 1] * BALL_SIZE * (1.02 + ((Math.random()**2 - 0.5) * 0.04)) + rackCenter.y,
                            rack[i + 2],
                        );
                        add = canAdd(ball);
                    }
                    balls.push(ball);
                    i += 3;
                }
                tableClear = false;
                downCount = 0;
                ballDownCtx.setTransform(1,0,0,1,0,0);
                ballDownCtx.clearRect(0,0,ballDownCtx.canvas.width, ballDownCtx.canvas.height);
            }
            function resolveCollisions(balls) {
                var minTime = 0, minObj, minBall, resolving = true, idx = 0, idx1, after = 0, e = 0, minU = 0;
                while (resolving && e++ < MAX_RESOLUTION_CYCLES) {
                    resolving = false;
                    minBall = minObj = undefined;
                    minTime = 1;
                    idx = 0;
                    for (const b of balls) {
                        idx1 = idx + 1;
                        while (idx1 < balls.length) {
                            const b1 = balls[idx1++];
                            const time = b.interceptBallTime(b1, after);
                            if (time !== undefined) {
                                if (time <= minTime) {
                                    minTime = time;
                                    minObj = b1;
                                    minBall = b;
                                    resolving = true;
                                }
                            }
                        }
                        for (const line of lines) {
                            const u = line.intercept(b);
                            const time = u >= after && u <= 1 ? u : undefined;
                            if (time !== undefined) {
                                if (time <= minTime) {
                                    minTime = time;
                                    minObj = line;
                                    minU = line.u;
                                    minBall = b;
                                    resolving = true;
                                }
                            }
                        }
                        idx ++;
                    }
                    if (resolving) {
                        if (minObj instanceof Ball) {
                            (game && !firstHit) && (game.firstHit = minObj);
                            minBall.collide(minObj, minTime);
                        } else { minBall.collideLine(minObj, minTime, minU) }
                        after = minTime;
                    }
                }
                if (e >= MAX_RESOLUTION_CYCLES) {

                }
            }


            function runSim(steps) {
                var i,allStopped = true;
                if (!tableClear) {
                    while (steps--) {
                        resolveCollisions(balls);
                        i = 0;
                        while (i < balls.length) {
                            const b = balls[i];
                            b.update();
                            if (b.dead) { balls.splice(i, 1) }
                            else {
                                if (b.speed > 0.1) { allStopped = false }
                                i++
                            }
                        }
                    }
                    if (balls.length === 1) {
                        tableClear = true;
                        setTimeout(rackBalls, 1000)
                        return false;
                    }
                    return allStopped;
                }
                return !tableClear;
            }
            function ballNearMouse() {
                return balls.find(ball => (ball.x - mouse.tx) ** 2 + (ball.y - mouse.ty) ** 2 < BALL_SIZE_SQR );
            }
                            // Saving positions permanently

          // Function to save positions to localStorage


// Load saved positions from localStorage on startup
function loadPositionsFromLocalStorage() {
    const savedPositions = JSON.parse(localStorage.getItem("positionSaves"));
    if (savedPositions) {
        positionSaves = savedPositions;
        positionSaves.current = positionSaves.length - 1;
    }
}

// Call this at the start of the program to load positions
loadPositionsFromLocalStorage();

function loadSaveBallPositions() {
    if (keys.Digit1) {
        keys.Digit1 = false;
        positionSaves.length > 14 && positionSaves.shift();
        positionSaves.push(balls.map(ball => ball.shadowOf()));
        positionSaves.current = positionSaves.length - 1;
        savePositionsToLocalStorage();  // Save to localStorage each time Digit1 is pressed
        message = "Ball positions saved to slot " + (positionSaves.length - 1) + ". Press 2 to cycle saves";
        messageTime = 220;
    }
    if (keys.Digit2) {
        keys.Digit2 = false;
        if (positionSaves.length) {
            rackBalls();
            balls.forEach(ball => ball.dead = true);
            positionSaves[positionSaves.current % positionSaves.length].forEach(shadow => {
                const ball = balls.find(ball => ball.id === shadow.id);
                ball && ball.fromShadow(shadow);
            });
            message = "Balls loaded from slot " + (positionSaves.current % positionSaves.length);
            messageTime = 120;
            positionSaves.current += 1;
            positionSaves.current %= positionSaves.length;
        } else {
            message = "Nothing to load";
            messageTime = 120;
        }
    } else if (keys.Digit0) {
        keys.Digit0 = false;
        rackBalls();
    }
}

            function drawPowerBar(max, power) {
                const w = canvas.width, h = canvas.height;
                const len = w * 0.25, left = w * 0.5 - len * 0.5;
                max = Math.min(1, max) ** 2;
                power = Math.max(0, Math.min(1, power)) ** 2;
                ctx.setTransform(1,0,0,1,0,0);
                ctx.fillStyle = "#000";
                ctx.fillRect(left, h - 10, len, 8);
                ctx.fillStyle = cssHexCol(powerBar.max.RGB1, powerBar.max.RGB2, max);
                ctx.fillRect(left + 2, h - 8, (len - 4) * max, 4);
                ctx.fillStyle = cssHexCol(powerBar.power.RGB1, powerBar.power.RGB2, max * power);;
                ctx.fillRect(left + 2, h - 8, (len - 4) * max * (power ** 2), 4);

            }
            var autoTime = 0;
            function doMouseInterface() {

                const B = balls[0];

                if (autoStart) {
                    autoTime ++;
                    if (autoTime < 50) {
                        mouse.tx = B.x - 30 - autoTime * TABLE_SCALE* 3;
                        mouse.ty = B.y - 3 * TABLE_SCALE;
                        if (autoTime > 5) {
                            mouse.button = 1;
                        }

                    } else if(autoTime < 60) {
                        mouse.tx = B.x - 70 * TABLE_SCALE* 3;
                        mouse.ty = B.y - 3 * TABLE_SCALE;;
                        mouse.button = 0;
                    } else {
                        //autoStart = false;
                    }
                }
                runToStop = 1;
                if (game && game.awaitingShotResult) {
                    game.update();
                    mouse.button = 0;
                }
                if (game && game.gameOver) {
                } else if (placeBalls) {
                    ballToPlace && ballToPlace.dead && (ballToPlace = undefined);
                    if (ballToPlace) {
                        ballToPlace.hold = true;
                        const oldx = ballToPlace.x;
                        const oldy = ballToPlace.y;
                        ballToPlace.x = mouse.tx;
                        ballToPlace.y = mouse.ty;
                        let ok = canAdd(ballToPlace);
                        if (!ok) {
                            ballToPlace.x = oldx;
                            ballToPlace.y = oldy;
                            const over = ballNearMouse();
                            if (over && over !== ballToPlace) {
                                const x = mouse.tx - over.x;
                                const y = mouse.ty - over.y;
                                const d = (x * x + y * y) ** 0.5;
                                ballToPlace.x = over.x + (x / d) * (BALL_SIZE * 2.001);
                                ballToPlace.y = over.y + (y / d) * (BALL_SIZE * 2.001)
                                ok = canAdd(ballToPlace);
                            }
                            if (!ok){
                                ballToPlace.x = oldx;
                                ballToPlace.y = oldy;
                            }
                        }
                        if (mouse.button === 1) {
                            if (ballToPlace.id === 1) {
                                placeBalls = false;
                                message = "Play mode.";
                                messageTime = 200;
                            }
                            ballToPlace.hold = false;
                            ballToPlace = undefined;
                            mouse.button = 0;
                        }
                    } else if ((mouse.button & 1) === 1) {
                        ballToPlace = ballNearMouse();
                        mouse.button = 0;
                    }
                } else if (B.hold) {
                    B.inPocket = false;
                    B.x = mouse.tx;
                    B.y = mouse.ty;
                    if(isInD(B) && canAdd(B)) {
                        if (mouse.button === 1) {
                            mouse.button = 0;
                            B.hold = false
                            wait = 20;
                        }
                    } else {
                        B.x = (frameCount / 30 | 0) % 2 ? head.x : - 100000;
                        B.y = head.y;
                    }
                } else {
                    var dx, dy, an, dist = 0;
                    if (lockAngle) {
                        if (!lockAngleLocked) {
                            const vx = Math.cos(fineAngle);
                            const vy = Math.sin(fineAngle);
                            dx =  mouse.tx - B.x;
                            dy =  mouse.ty - B.y;
                            an = Math.angleBetween(vx, vy, dx, dy)
                            dist = lockDistTemp;
                        } else {
                            const vx = Math.cos(lockAngleAt);
                            const vy = Math.sin(lockAngleAt);
                            dx =  mouse.tx - B.x;
                            dy =  mouse.ty - B.y;
                            dist = vx * dx + vy * dy;
                            lockDistTemp = dist;
                            if (dist < -BALL_SIZE && mouse.button === 0) {
                                mouse.pull = 0;
                                mouse.spring = 0;
                                mouse.speed = 0;
                                mouse.pos = 0;
                                mouse.spin = 0;
                                lockAngleLocked = lockAngle = false;
                                mouse.button = 0;
                            }
                        }
                    } else {
                        dx =  mouse.tx - B.x;
                        dy =  mouse.ty - B.y;
                        an  = mouse.angle = Math.atan2(dy , dx);
                        dist = (dx * dx + dy * dy) ** 0.5;
                    }
                    if ((mouse.button & 1) === 1) {
                        mouse.pull = Math.min(maxPull / 5, (dist  - mouse.spring) / (10));
                        if ((mouse.button & 4) === 4) {
                            if (lockAngleLocked) {
                                lockAngle = false;
                                an = lockAngleAt;
                            }
                            if (!lockAngle) {
                                lockAngleAt = fineAngleStart = fineAngle = an;
                                lockAngle = true;
                                lockAngleLocked = false;
                                lockDistTemp = dist;
                            } else {
                                fineAngle += an
                                an = mouse.angleToHit = mouse.angle = lockAngleAt = fineAngleStart + (fineAngle - fineAngleStart) / 100;
                            }
                        } else if(lockAngle) {
                            lockAngleLocked = true;
                            an = mouse.angleToHit = mouse.angle = lockAngleAt
                        } else { mouse.angleToHit = an }

                        mouse.angleToHit = an;
                        mouse.pos = mouse.spring;
                        mouse.spring += mouse.pull;
                        mouse.spring *= 0.95;
                        SHOW_GUIDES && findFirstHit(B, an, balls)
                    } else {
                        if(lockAngle) {
                            mouse.angle = lockAngleAt
                        }
                        if (mouse.speed === 0) {
                            if (!placeBalls && (mouse.button & 4) === 4) {
                                message = "Place mode. Click a ball to move it, click agian to place, place the black ball in your desired position ";

                                                           messages.length = 0;
                                messages.push("After placing it back in the rack the game automatically goes back to play mode",



                                    "Press 1 save ball positions in next save slot.",
                                    "Press 2 to load. Will cycle through save slots"
                                );
                                messageTime = 500;
                                placeBalls = true;
                                mouse.button = 0;
                            } else {
                                SHOW_GUIDES && findFirstHit(B, an, balls, true);
                            }
                        }
                        if (mouse.pull) {
                            if (allowSpinControl && mouse.spin) {
                                const sp = Math.max(15,mouse.spring - mouse.spinPower);
                                mouse.spring = mouse.spinPower;
                                mouse.spinPower = sp;
                            }
                            mouse.pos = mouse.spring;
                            mouse.speed = 0;
                        }
                        mouse.pull = 0;
                        mouse.spring *= 0.5;
                        mouse.speed += mouse.spring
                        mouse.pos -= mouse.speed;
                        if (mouse.pos < 0) {
                            const power = (Math.min(70, (mouse.speed * mouse.mass) / BALL_MASS) / 70) ** 2 * 70;
                            B.vx = Math.cos(mouse.angleToHit + Math.PI) * power;
                            B.vy = Math.sin(mouse.angleToHit + Math.PI) * power;
                            mouse.pull = 0;
                            mouse.spring = 0;
                            mouse.speed = 0;
                            mouse.pos = 0;
                            mouse.spin = 0;
                            lockAngleLocked = lockAngle = false;
                            game && game.shoots();
                        }
                    }

                    if (SHOW_POWER_BAR) {
                        dist = Math.min(dist, ctx.canvas.width * TABLE_SCALE * 0.1);
                        drawPowerBar(dist / (ctx.canvas.width * TABLE_SCALE * 0.1), mouse.pos / (dist * 0.56));
                        ctx.setTransform(TABLE_SCALE,0,0,TABLE_SCALE,GAME_LEFT, GAME_TOP);
                    }
                    renderQue(B);
                }
            }

            function mainLoop() {
                var allStopped;
                wait && (wait--);
                frameCount ++;
                ctx = ctxGame;
                ctx.clearRect(0,0,ctx.canvas.width, ctx.canvas.height);
                ctx.fillStyle = tableGradient;//TABLE_COLOR;
                ctx.strokeStyle = TABLE_MARK_COLOR;
                ctx.lineWidth = TABLE_MARK_LINE_WIDTH;
                ctx.fill(tableEdge);
                ctx.stroke(head.path);
                loadSaveBallPositions();
                if (touches.hasTouched) {
                    touches.preventDefault = true;
                    if(touches.hasPoints) {
                        const ps = touches.points;
                        if (ps.length > 1) {
                            mouse.button |= 1;
                            mouse.x = ps[0].x;
                            mouse.y = ps[0].y;
                        } else if(ps.length === 1) {
                            mouse.x = ps[0].x;
                            mouse.y = ps[0].y;
                            mouse.button &= 0b110;
                        } else {
                            mouse.button = 0;
                        }
                    }
                }

                allStopped = runSim(slowDevice ? 2 * runToStop : runToStop);
                wait > 0 && (allStopped = false);
                if (allStopped && !placeBalls && !balls[0].hold) { simpleGuide(balls[0]) }
                renderBalls();
                mouse.tx = (mouse.x - GAME_LEFT) / TABLE_SCALE;
                mouse.ty = (mouse.y - GAME_TOP) / TABLE_SCALE;

                if (!allStopped && (mouse.button & 4) === 4) {
                    runToStop += 5;
                    mouse.button = 0;
                }
                ctx = ctxMain;
                ctx.setTransform(1,0,0,1,0,0);
                ctx.clearRect(0,0,ctx.canvas.width, ctx.canvas.height);
                ctx.setTransform(TABLE_SCALE,0,0,TABLE_SCALE,GAME_LEFT, GAME_TOP);
                ctxMain.drawImage(gameCanvas, 0,0);
                ctxMain.drawImage(ballDownCan, 0, -BALL_SIZE * DOWN_DISPLAY_BALL_OFFSET, ballDownCtx.canvas.width * DOWN_DISPLAY_BALL_SCALE, ballDownCtx.canvas.height * DOWN_DISPLAY_BALL_SCALE);

                if (allStopped) {
                    if (autoStart && autoTime > 60) {
                        autoStart = false;
                        keys.Digit0 = true; // rackem
                    }
                    doMouseInterface();

                }
                if(HAS_GAME_PLAY_API && sunk.balls.length) { sunk.animate() }

                if (message) {
                    ctx.setTransform(1,0,0,1,ctx.canvas.width / 2, GAME_TOP - MESSAGE_FONT_SIZE + MESSAGE_OFFSET);
                    ctx.font = MESSAGE_FONT_SIZE + "px Arial";
                    ctx.textAlign = "center";
                    ctx.fillStyle = "#FFF";
                    ctx.strokeStyle = "#000";
                    ctx.lineWidth = MESSAGE_OUTLINE;
                    ctx.lineJoin = "round";
                    if (messageTime) {
                        if (messageTime < 30) { ctx.globalAlpha = (messageTime / 30) ** 0.5  }
                        messageTime --;
                        ctx.strokeText(message, 0, 0);
                        ctx.fillText(message, 0, 0);
                        ctx.globalAlpha = 1;
                    } else {
                        if (messages.length)  {
                            message = messages.shift();
                            messageTime = 320;

                        } else {
                            message = undefined;
                        }

                    }
                }
                mouse.oldX = mouse.tx;
                mouse.oldY = mouse.ty;
                requestAnimationFrame(mainLoop);
            }


            function mathExt() {
                Math.TAU = Math.PI * 2;
                Math.PI90 = Math.PI * 0.5;
                Math.rand = (min, max) => Math.random() * (max - min) + min;
                Math.randP = (min, max, pow = 2) => Math.random() ** pow * (max - min) + min;
                Math.randI = (min, max) => Math.random() * (max - min) + min | 0; // only for positive numbers 32bit signed int
                Math.randItem = arr => arr[Math.random() * arr.length | 0]; // only for arrays with length < 2 ** 31 - 1
                Math.polarArray = (ang, dist, ox = 0, oy = 0) => [ox + Math.cos(ang) * dist, oy + Math.sin(ang) * dist];
                Math.angleBetween = (xa, ya, xb, yb) => {
                    const l = ((xa * xa + ya * ya) * (xb * xb + yb * yb)) ** 0.5;
                    var ang = 0;
                    if (l !== 0) {
                        ang = Math.asin((xa  * yb  - ya * xb) / l);
                        if (xa  * xb  + ya * yb < 0) { return (ang < 0 ? -Math.PI: Math.PI) - ang }
                    }
                    return ang;
                }

                Math.circlesInterceptUnitTime = (x1, y1, vx1, vy1, x2, y2, vx2, vy2, r1, r2) => {
                    //if (vx1 * vx2 + vy1 * vy2 < 0)  { return -1 }
                    const rr = r1 + r2;
                    const vx = vx1 - vx2;
                    const vy = vy1 - vy2;
                    var l = (vx * vx + vy * vy) ** 0.5;
                    const px = x2 - x1;
                    const py = y2 - y1;
                    var dist = (px * px + py * py) ** 0.5;
                    if (l + rr < dist) { return 2 }
                    const u = (px * vx + py * vy) / (l * l);
                    var x = x1 + vx * u - x2;
                    var y = y1 + vy * u - y2;
                    const distSqr = x * x + y * y;
                    return (u * l - (rr * rr - distSqr) ** 0.5) / l;
                }
                Math.quadRoots = (a, b, c) => { // find roots for quadratic
                    if (Math.abs(a) < 1e-6) { return b != 0 ? [-c / b] : []  }
                    b /= a;
                    var d = b * b - 4 * (c / a);
                    if (d > 0) {
                        d = d ** 0.5;
                        return  [0.5 * (-b + d), 0.5 * (-b - d)]
                    }
                    return d === 0 ? [0.5 * -b] : [];
                }

            }
            </script>
    </main>

</x-app-layout>
